<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ColorProduct;
use App\Models\Order;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Color;
use App\Models\Product;
use App\Models\Review;
use App\Models\Size;
use App\Models\SizeColorProduct;
use App\Models\Status;

class OrderController extends Controller
{
    public function index()
    {
        try {
            $orders = Order::with(['user','products', 'statuses', 'paymentMethod'])->get()
            ->map(function($order){
                $time = Carbon::parse($order->created_at)->format('H:i:s d-m-Y');
                $order->time = $time;
                return $order;
            })->sortByDesc('created_at')->values();
            Log::error($orders);
            return response()->json($orders);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to retrieve orders'], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'total_price' => 'required|numeric',
            'fullname'=> 'required|string',
            'address'=> 'required|string', 
            'email'=> 'required|string', 
            'phone_number'=> 'required|string',
            'city'=> 'required|string',
            'district'=> 'required|string',
            'commune'=> 'required|string',
            'note'=> 'nullable|string',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.new_price' => 'required|string',
            'products.*.old_price' => 'required|string',
            'products.*.id' => 'required|exists:products,id',
            'products.*.color_id' => 'required|exists:colors,id',
            'products.*.size_id' => 'required|exists:sizes,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
        try {
            DB::transaction(   
                function () use ($request) { 
                    $order = Order::create($request->except(['products']));
                    $order->statuses()->attach(1);
                    $products = $request->input('products', []);

                    foreach ($products as &$product) {
                        $order->products()->attach($product['id'], [
                            'quantity' => $product['quantity'], 
                            'color_id' => $product['color_id'],
                            'size_id' => $product['size_id'], 
                            'new_price' => $product['new_price'],
                            'old_price' => $product['old_price']
                        ]);
                    }
                },5
            );
        } catch (\Exception $e) {
            Log::error('Order creation failed: ' . $e->getMessage(), ['exception' => $e]);
            if ($e->getCode() == '23000') { // Mã lỗi ràng buộc (constraint violation)
                return response()->json(['errors' => $e->getMessage()], 400);
            }
            return response()->json(['error' => 'Unable to create order'], 500);
        }
        return response()->json(['success' => true], 201);
    }

    public function show($id)
    {
        try {
            $order = Order::with(['paymentMethod', 'products.reviews.images', 'statuses'])->findOrFail($id);
            $formattedDate = Carbon::parse($order->created_at)->format('H:i d-m-Y');
            foreach($order->statuses as &$status){
                $formattedDateStatus = Carbon::parse($status->pivot->created_at)->format('H:i d-m-Y');
                $status->formatted_created_at = $formattedDateStatus;
            }
            $order->formatted_created_at = $formattedDate;

            foreach($order->products as $product ) {
                $productId = $product->id;
                $colorId = $product->pivot->color_id;
                $sizeId = $product->pivot->size_id;
                $colorProduct = ColorProduct::with(['images'])->where('product_id', $productId)->where('color_id', $colorId)->first();
                $imageUrl = $colorProduct->images[0]->url;
                $product->color = Color::findOrFail($colorId)->name;
                $product->size = Size::findOrFail($sizeId)->name;
                $product->imageUrl = $imageUrl;
            }
            return response()->json($order);
        } catch (ModelNotFoundException $e) {
            Log::error('Order getting failed: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Order not found'], 404);
        } catch (\Exception $e) {
            Log::error('Order getting failed: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Unable to retrieve order'], 500);
        }
    }
    public function getByUserId(Request $request)
    {
        try {
            $UserId = $request->user()->id;
            $orders = Order::select(['id', 'total_price', 'created_at'])
            ->with(['products', 'statuses'])
            ->where('user_id', $UserId)
            ->get()
            ->map(function($order) use ($UserId) {
                // Chuyển đổi định dạng của 'created_at'
                $formattedDate = Carbon::parse($order->created_at)->format('H:i d-m-Y');
                $order->formatted_created_at = $formattedDate;
                
                
                foreach($order->products as $product ) {
                    $productId = $product->id;
                    $colorId = $product->pivot->color_id;
                    $sizeId = $product->pivot->size_id;
                    $review = Review::with(['images'])->where('order_id', $order->id)->where('product_id', $productId)->where('user_id', $UserId)->first();
                    $colorProduct = ColorProduct::with(['images'])->where('product_id', $productId)->where('color_id', $colorId)->first();
                    $imageUrl = $colorProduct->images[0]->url;
                    $product->color = Color::findOrFail($colorId)->name;
                    $product->size = Size::findOrFail($sizeId)->name;
                    $product->imageUrl = $imageUrl;
                    $product->review =  $review ;
                }
                return $order;
            })
            ;
            

            return response()->json($orders);
        } catch (ModelNotFoundException $e) {
            Log::error('Order getting failed: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Order not found'], 404);
        } catch (\Exception $e) {
            Log::error('Order getting failed: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Unable to retrieve order'], 500);
        }
    }

    public function getAllOrderReviews(Request $request)
    {
        try {
            $UserId = $request->user()->id;
            $orders = Order::select(['id', 'total_price', 'created_at'])
            ->with(['products', 'statuses', 'reviews'])
            ->where('user_id', $UserId)
            ->get()
            ->map(function($order){
                // Chuyển đổi định dạng của 'created_at'
                $formattedDate = Carbon::parse($order->created_at)->format('H:i d-m-Y');
                $order->formatted_created_at = $formattedDate;
                foreach($order->products as $product ) {
                    $productId = $product->id;
                    $colorId = $product->pivot->color_id;
                    $sizeId = $product->pivot->size_id;
                    $colorProduct = ColorProduct::with(['images'])->where('product_id', $productId)->where('color_id', $colorId)->first();
                    $imageUrl = $colorProduct->images[0]->url;
                    $product->color = Color::findOrFail($colorId)->name;
                    $product->size = Size::findOrFail($sizeId)->name;
                    $product->imageUrl = $imageUrl;
                }
                return $order;
            })
            ;
            

            return response()->json($orders);
        } catch (ModelNotFoundException $e) {
            Log::error('Order getting failed: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Order not found'], 404);
        } catch (\Exception $e) {
            Log::error('Order getting failed: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['error' => 'Unable to retrieve order'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'sometimes|required|exists:users,id',
                'total_price' => 'sometimes|required|numeric',
                'status' => 'sometimes|required|string',
                'products' => 'sometimes|array',
                'products.*.id' => 'required_with:products|exists:products,id',
                'products.*.quantity' => 'required_with:products|integer|min:1',
            ]);

            $order = Order::findOrFail($id);
            $order->update($validated);

            if (isset($validated['products'])) {
                $order->products()->detach();
                foreach ($validated['products'] as $product) {
                    $order->products()->attach($product['id'], ['quantity' => $product['quantity']]);
                }
            }

            return response()->json($order->load('products'));
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Order not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to update order'], 500);
        }
    }
    public function comfirm(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'listOrder' => 'required|array',
            ]);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }
            DB::transaction(
                function () use ($request){
                    foreach ($request->listOrder as $orderId) {
                        $order = Order::with(['statuses'])->findOrFail($orderId);
                        if ($order->statuses[0]->id == 1) {
                            // Add new status to the pivot table
                            $order->statuses()->attach(2); // Assuming 2 is the ID for the new status
                        }
                    }
                }
                ,5
            );    
            return response()->json(['message' => 'Orders confirmed successfully.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Order not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while confirming orders.'], 500);
        }
    }
    public function nextStatus($id)
    {
        try {
            $order = Order::with(['statuses', 'products'])->findOrFail($id);
            if ($order->statuses[0]->id >= 2 && $order->statuses[0]->id <= 4 ) {
                $order->statuses()->attach($order->statuses[0]->id + 1); 
                $orderNew = Order::with(['statuses'])->findOrFail($id);
                return response()->json($orderNew);
            }else if ($order->statuses[0]->id == 5 ) {
                foreach($order->products as $product){
                    $quantityOrder = $product->pivot->quantity;
                    $color_id = $product->pivot->color_id;
                    $size_id = $product->pivot->size_id;
                    $colorProductId = ColorProduct::where('color_id',$color_id)->where('product_id', $product->id)->first()->id;
                    $size = SizeColorProduct::where('size_id', $size_id)->where('color_product_id', $colorProductId)->first();
                    $quantitySize = $size->quantity;
                    if($quantitySize < $quantityOrder) return response()->json(['error' => 'Tồn kho không đủ'], 500);
                    else{
                        $newQuantity = $quantitySize -  $quantityOrder;
                        $size->update([
                            'quantity' => $newQuantity
                        ]);
                    }
                }
                $order->statuses()->attach(7); 
                $orderNew = Order::with(['statuses'])->findOrFail($id);
                return response()->json($orderNew);
            }
                       
            return response()->json(['message' => 'Orders confirmed successfully.']);
        } catch (ModelNotFoundException $e) {
            Log::error($e);
            return response()->json(['error' => 'Order not found'], 404);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['error' => 'An error occurred while confirming orders.'], 500);
        }
    }
    public function cancelOrder(Request $request, $id){
        try {
            $order = Order::with(['statuses'])->findOrFail($id);
            if ($order->statuses[0]->id == 1 && $request->user()->id == $order->user_id ) {
                $order->statuses()->attach(6); 
                $orderNew = Order::with(['statuses'])->findOrFail($id);
                return response()->json($orderNew);
            }
            return response()->json(['error' => 'eror'], 500);
        } catch (ModelNotFoundException $e) {
            Log::error($e);
            return response()->json(['error' => 'Order not found'], 404);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['error' => 'An error occurred while confirming orders.'], 500);
        }
    } 

    public function destroy($id)
    {
        try {
            $order = Order::findOrFail($id);
            $order->delete();
            return response()->json(null, 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Order not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to delete order'], 500);
        }
    }
    public function overview()
    {
        try {
            $orders = Order::with(['products', 'statuses'])->get();
            $total_order = Count($orders);
            $order_done = $orders->filter(function ($order) {
                return $order->statuses[0]->id == 7;
            });
            $total_order_done = Count($order_done);
            $revenue = 0;
            foreach($order_done as $order){
                $revenue += $order->total_price;
            }

            $totalQuantity = 0; // Khởi tạo biến tổng số lượng
            $products = Product::with(['colorProduct.sizes'])->get()
                ->map(function ($product) {
                    $product->total_quantity_sold = $product->orders()->sum('order_product.quantity');
                    return $product;
                })->sortByDesc('total_quantity_sold')->values();
            foreach($products as $product) {
                foreach ($product->colorProduct as $colorProduct) {
                    $totalQuantity += $colorProduct->sizes->sum('quantity'); // Tính tổng số lượng từ mỗi màu
                }
            };
            $categories = Category::with(['products'])->get()
            ->map(function ($category) {
                $total_quantity_sold = 0;
                foreach($category->products as $product){
                    $total_quantity_sold += $product->orders()->sum('order_product.quantity');
                }
                $category->total_quantity_sold = $total_quantity_sold;
                $return = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'product_count' => $category->product_count,
                    'total_quantity_sold'=>$total_quantity_sold,
                ];
                return $return;
            })->sortByDesc('total_quantity_sold')->values();
            $product_best_sale = $products->take(5)->map(function($product){
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'total_sale' => $product->total_quantity_sold,
                ];
            });
            $response = [
                'total_order'=>  $total_order,
                'total_order_done' =>$total_order_done,
                'revenue' => $revenue,
                'total_quantity_product' => $totalQuantity,
                'product_best_sale' => $product_best_sale,
                'category_best_sale'=> $categories->take(5),
            ];
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to retrieve orders'], 500);
        }
    }
}