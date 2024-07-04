<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ColorProduct;
use App\Models\Order;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Color;
use App\Models\Review;
use App\Models\Size;


class OrderController extends Controller
{
    public function index()
    {
        try {
            $orders = Order::with('products')->get();
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
}