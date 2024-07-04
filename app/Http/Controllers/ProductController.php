<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Category;
use App\Models\Color;
use App\Models\ColorProduct;
use App\Models\ImageColorProduct;
use App\Models\SizeColorProduct;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Log;
use Google\Cloud\Storage\StorageClient;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $products = Product::with(['categories', 'tag','type','colorProduct.color', 'colorProduct.sizes', 'colorProduct.images'])
                ->get()
                ->map(function ($product) {
                    $totalQuantity = 0; // Khởi tạo biến tổng số lượng
                    foreach ($product->colorProduct as $colorProduct) {
                        $totalQuantity += $colorProduct->sizes->sum('quantity'); // Tính tổng số lượng từ mỗi màu
                    }
                    $product->total_quantity = $totalQuantity; 
                    $product->total_orders = $product->orders()->count();
                    $product->total_quantity_sold = $product->orders()->sum('order_product.quantity');
                    return $product;
                });
            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to retrieve products'], 500);
        }
    }

    public function getAll()
    {
        try {
            $products = Product::all(['id', 'name']);

            return response()->json($products);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to retrieve products'], 500);
        }
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // Validate dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|integer',
            'sale' => 'nullable|string',
            'tag_id' => 'required|integer|exists:tags,id',
            'description' => 'required|string',
            'product_type_id' => 'required|integer|exists:product_types,id',
            'categories' => 'required|array',
            'colors' => 'required|array',
            'colors.*.id' => 'required|integer',
            'colors.*.name' => 'required|string|max:255',
            'colors.*.code' => 'required|string|max:9',
            'colors.*.imgs' => 'required|array',
            'colors.*.imgs.*.name' => 'required|string|max:255',
            'colors.*.imgs.*.url' => 'required|string|max:255',
            'colors.*.sizes' => 'required|array',
            'colors.*.sizes.*.id' => 'required|integer',
            'colors.*.sizes.*.quantity' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
        try{
            DB::transaction(
                function () use ($request) {
                    $product = Product::create($request->except(['categories', 'colors']));
                    //categories
                    $categoryIds = $request->input('categories', []);
                  
                    $product->categories()->sync($categoryIds);
                    //colors
                    $colors = $request->input('colors', []);
                    foreach ($colors as &$color) {
                        if ($color['id'] == -1) {
                            $newColor = Color::create(['name' => $color['name'], 'code' => $color['code']]);
                            $color['id'] = $newColor->id;
                        }
                    }
                    $colorIds = array_column($colors, 'id');
                    $product->colors()->sync($colorIds);
                    
                    // Lưu các thông tin liên quan đến từng color
                    foreach ($colors as &$color) {
                        // Lấy color_product_id từ bảng pivot
                        $colorProductId = DB::table('colors_products')
                        ->where('product_id', $product->id)
                        ->where('color_id', $color['id'])
                        ->first()
                        ->id;
                        // Lưu imgs
                        foreach ($color['imgs'] as $img) {
                            DB::table('images_colors_product')->insert([
                                'name' => $img['name'],
                                'url' => $img['url'],
                                'color_product_id' => $colorProductId,
                            ]);
                        }

                        // Lưu sizes
                        foreach ($color['sizes'] as $size) {
                            DB::table('sizes_products')->insert([
                                'color_product_id' => $colorProductId,
                                'size_id' => $size['id'],
                                'quantity' => $size['quantity'],
                            ]);
                        }
                    }
                }, 
                5
            );
        }catch (QueryException $e) {
            
            if ($e->getCode() == '23000') { // Mã lỗi ràng buộc (constraint violation)
                return response()->json(['errors' => 'Duplicate entry or foreign key constraint'], 400);
            }
            return response()->json(['errors' => $e->getMessage()], 400); // Bad Request nếu có lỗi trong query
        }
        // Trả về JSON response
        return response()->json(['success' => true]);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $product = Product::with(['categories', 'tag','type','colorProduct.color', 'colorProduct.sizes.size', 'colorProduct.images'])->findOrFail($id);

            $totalOrders = $product->orders()->count();
            $totalQuantity = $product->orders()->sum('order_product.quantity');

            $productData = $product->toArray();
            $productData['total_orders'] = $totalOrders;
            $productData['total_quantity'] = $totalQuantity;

            return response()->json($productData);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Product not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to retrieve product'], 500);
        }
    }

    public function getProduct($id)
    {
        try {
            $product = Product::with(['categories', 'tag','type','colorProduct.color', 'colorProduct.sizes.size', 'colorProduct.images'])->findOrFail($id);
            $categoryIds = $product->categories->pluck('id')->toArray();

        // Tìm các sản phẩm khác thuộc cùng các categories đó, ngoại trừ sản phẩm hiện tại
            $productSuggest = Product::with(['categories', 'tag', 'type', 'colorProduct.color', 'colorProduct.sizes.size', 'colorProduct.images'])
                ->whereHas('categories', function ($query) use ($categoryIds) {
                    $query->whereIn('categories.id', $categoryIds);
                })
                ->where('id', '!=', $id)
                ->inRandomOrder()
                ->take(20)
                ->get();
            $product->product_suggest =  $productSuggest;
            return response()->json($product);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Product not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to retrieve product'], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|integer',
            'sale' => 'nullable|string',
            'tag_id' => 'sometimes|required|integer|exists:tags,id',
            'description' => 'sometimes|required|string',
            'product_type_id' => 'sometimes|required|integer|exists:product_types,id',
            'categories' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {
            DB::transaction(function () use ($request, $id) {
                $product = Product::findOrFail($id);
                $product->update($request->except(['categories', 'colors']));

                if ($request->has('categories')) {
                    $categoryIds = $request->input('categories', []);
                    $product->categories()->sync($categoryIds);
                }
            }, 5);
        } catch (ModelNotFoundException $e) {
            return response()->json(['errors' => 'Product not found'], 404);
        } catch (QueryException $e) {
            if ($e->getCode() == '23000') {
                return response()->json(['errors' => 'Duplicate entry or foreign key constraint'], 400);
            }
            return response()->json(['errors' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json(['errors' => 'Unable to update product'], 500);
        }

        return response()->json(['success' => true]);
    }

    public function deleteImageFromProduct($imageColorProductId)
    {
        try {
            $img = ImageColorProduct::findOrFail($imageColorProductId);

            $request = new Request();
            $request->replace(['url' => $img->url]);
            $uploadController = new UploadController();
            $response = $uploadController->destroy($request);

            $img->delete();

            return response()->json(['message' => 'Image deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Image not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete image', 'message' => $e->getMessage()], 500);
        }
    }
    public function addImageFromProduct(Request $request, $colorProductId)
    {
        $request->validate([
            'file' => 'required|image|max:10240',
        ]);
        
            $colorProduct = ColorProduct::findOrFail($colorProductId);
            $googleConfigFile = file_get_contents(config_path('service-account.json'));
            $storage = new StorageClient([
                'keyFile' => json_decode($googleConfigFile, true)
            ]);
            $storageBucketName = config('googlecloud.storage_bucket');
            $bucket = $storage->bucket($storageBucketName);
            $file_request = $request->file('file');
            $image_path = $file_request->getRealPath();
            $file_name = time().'.'.$file_request->extension();
            $fileSource = fopen($image_path, 'r');
            $googleCloudStoragePath = 'laravel-upload/' . $file_name;
            $bucket->upload($fileSource, [
                'predefinedAcl' => 'publicRead',
                'name' => $googleCloudStoragePath
            ]);
            $url = 'https://storage.googleapis.com/hungnd/'. $googleCloudStoragePath;
            $return = ImageColorProduct::create([
                'name' => $file_name,
                'url' => $url,
                'color_product_id' => $colorProductId,
            ]);
            return response()->json($return, 200);
            
    }

    public function deleteColorFromProduct($colorProductId)
    {
        try {
            $colorProduct = ColorProduct::with(['images'])->findOrFail($colorProductId);
            $uploadController = new UploadController();
            foreach($colorProduct['images'] as $img){
                $request = new Request();
                $request->replace(['url' => $img->url]);
                // Tạo instance của UploadController
                $response = $uploadController->destroy($request);
            }
            $colorProduct->delete();
            return response()->json(['message' => 'Color-Product deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            // Xử lý khi không tìm thấy ImageColorProduct
            return response()->json(['error' => 'Color-Product not found'], 404);
        } catch (\Exception $e) {
            // Xử lý các lỗi khác
            return response()->json(['error' => 'Failed to delete Color-Product', 'message' => $e->getMessage()], 500);
        }
    }
    public function addColorFromProduct(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:9',
            'imgs' => 'required|array',
            'imgs.*.name' => 'required|string|max:255',
            'imgs.*.url' => 'required|string|max:255',
            'sizes' => 'required|array',
            'sizes.*.id' => 'required|integer',
            'sizes.*.quantity' => 'required|integer',
        ]);
        
        try {
            DB::transaction(function () use ($request, $productId) {
                $product = Product::findOrFail($productId);
                $color = $request;
                if ($color['id'] == -1) {
                    $newColor = Color::create(['name' => $color['name'], 'code' => $color['code']]);
                    $color['id'] = $newColor->id;
                }
                $product->colors()->attach($color['id']);
                
                $colorProductId = DB::table('colors_products')
                ->where('product_id', $product->id)
                ->where('color_id', $color['id'])
                ->first()
                ->id;
                foreach ($color['imgs'] as $img) {
                    DB::table('images_colors_product')->insert([
                        'name' => $img['name'],
                        'url' => $img['url'],
                        'color_product_id' => $colorProductId,
                    ]);
                }

                // Lưu sizes
                foreach ($color['sizes'] as $size) {
                    DB::table('sizes_products')->insert([
                        'color_product_id' => $colorProductId,
                        'size_id' => $size['id'],
                        'quantity' => $size['quantity'],
                    ]);
                }
            }, 5);

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Product not found'], 404);
        } catch (\Exception $e) {
            // Xử lý các lỗi khác
            return response()->json(['error' => 'Failed to add Product', 'message' => $e->getMessage()], 500);
        }
        return response()->json(['success' => true]);
    }
    public function updateSizesProduct(Request $request, $colorProductId){
        $validator = Validator::make($request->all(), [
            'sizes' => 'required|array',
            'sizes.*.id' => 'required|integer',
            'sizes.*.quantity' => 'required|integer',
        ]);
        SizeColorProduct::where('color_product_id', $colorProductId)->delete();
        foreach ($request->sizes as $size){
            SizeColorProduct::create([
                'color_product_id' => $colorProductId,
                'size_id' => $size['id'],
                'quantity' => $size['quantity'],
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();

            return response()->json(null, 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Product not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to delete product'], 500);
        }

    }
}