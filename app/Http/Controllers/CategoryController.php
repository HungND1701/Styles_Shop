<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use App\Models\ImageColorProduct;
use App\Models\SizeColorProduct;
use Illuminate\Http\Response;
use Exception;

class CategoryController extends Controller
{
    public function index()
    {
        //
    }

    public function getData()
    {
        try{
            $categories = Category::with(['products'])->get();
            
            return response()->json($categories);

        }catch(QueryException $e){
            return response()->json(['errors' => $e->getMessage()], 400); // Bad Request nếu có lỗi trong query
        }
    }

    public function attachProduct(Request $request, $categoryId)
    {
        try {
            $validatedData = $request->validate([
                'product_id' => 'required|integer|exists:products,id'
            ]);

            $category = Category::findOrFail($categoryId);
            $productId = $validatedData['product_id'];

            // Gắn sản phẩm vào category
            $category->products()->attach($productId);
            $category->load('products') ;
            $category->products->each(function ($product) {
                $product->img_url = ImageColorProduct::whereIn('color_product_id', function ($query) use ($product) {
                    $query->select('id')->from('colors_products')->where('product_id', $product->id);
                })->orderBy('id', 'asc')->limit(1)->value('url');
                $product->total_quantity = SizeColorProduct::whereIn('color_product_id', function ($query) use ($product) {
                    $query->select('id')->from('colors_products')->where('product_id', $product->id);
                })->sum('quantity');
            });
            return response()->json(
                $category
            , Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Could not attach product to category',
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
    }
    public function getCategoryWithProduct($id)
    {
        try {
            $category = Category::with('products.type','products.tag', 'products.colorProduct.color', 'products.colorProduct.sizes.size', 'products.colorProduct.images','products.reviews')->find($id);

            return response()->json($category, Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Could not fetch category',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getCategory($id)
    {
        try{
            $category = Category::with(['products'])->find($id);
            
            if (!$category) {
                return response()->json(['message' => 'Category not found'], 404);
            }
            $category->products->each(function ($product) {
                $product->img_url = ImageColorProduct::whereIn('color_product_id', function ($query) use ($product) {
                    $query->select('id')->from('colors_products')->where('product_id', $product->id);
                })->orderBy('id', 'asc')->limit(1)->value('url');
                $product->total_quantity = SizeColorProduct::whereIn('color_product_id', function ($query) use ($product) {
                    $query->select('id')->from('colors_products')->where('product_id', $product->id);
                })->sum('quantity');
            });
    
            return response()->json($category, 200);

        }catch(QueryException $e){
            return response()->json(['errors' => $e->getMessage()], 400); // Bad Request nếu có lỗi trong query
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
            'sub_title' => 'required|string|max:255',
            'banner_img_url' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
        try{
            $category = Category::create([
                'name' => $request->name,
                'sub_title' => $request->sub_title,
                'banner_img_url' => $request->banner_img_url,
                'description' => $request->description,
            ]);
            // Trả về JSON response
            return response()->json($category, 201);
        }catch(QueryException $e){
            return response()->json(['errors' => $e->getMessage()], 400); // Bad Request nếu có lỗi trong query
        }
        // Tạo category mới
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
    public function show(Category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        
        $category = Category::with('products')->find($id);

        if (!$category) {
            return response()->json(['message' => 'Không tìm thấy danh mục'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'sub_title' => 'required|string|max:255',
            'banner_img_url' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category->name = $request->input('name');
        $category->description = $request->input('description');
        $category->sub_title = $request->input('sub_title');
        $category->banner_img_url = $request->input('banner_img_url');
        $category->save();

        return response()->json($category, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully'], 200);
    }
}
