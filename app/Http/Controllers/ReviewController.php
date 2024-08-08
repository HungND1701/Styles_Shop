<?php

namespace App\Http\Controllers;

use App\Models\Color;
use App\Models\ColorProduct;
use App\Models\Review;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\ImageReview;
use App\Models\Order;
use App\Models\Size;
use Illuminate\Support\Carbon;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'order_id' => 'required|exists:orders,id',
                'review' => 'required|string',
                'rating' => 'required|integer|min:1|max:5',
                'images' => 'required|array',
                'images.*.url' => 'required|string|max:255'
            ]);
            $user_id = $request->user()->id;
            DB::beginTransaction();

            $review = Review::create([
                'user_id' => $user_id,
                'product_id' => $request->product_id,
                'order_id' => $request->order_id,
                'review' => $request->review,
                'rating' => $request->rating,
            ]);
            foreach ($request->images as $image) {
                ImageReview::create([
                    'url' => $image['url'],
                    'review_id' => $review->id,
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Tạo đánh giá thành công', 'review' => $review], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Tại đánh giá thất bại', 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $review = Review::with(['images', 'replies'])->findOrFail($id);
            return response()->json(['review' => $review], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Review not found', 'message' => $e->getMessage()], 404);
        }
    }
    public function getAll()
    {
        try {
            $review = Review::with(['user','images','replies'])->get()
            ->map(function($review){
                $time = Carbon::parse($review->created_at)->format('H:i:s d-m-Y');
                $review->time = $time;
                if(count($review->replies) == 0) $review->status = 'Chưa phản hồi';
                else $review->status = 'Đã phản hồi';
                return $review;
            })
            ->sortByDesc('created_at')->values();
            return response()->json($review, 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Review not found', 'message' => $e->getMessage()], 404);
        }
    }

    public function getByUserId(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $reviews = Review::with(['images','replies'])->where('user_id',$userId)->get()
            ->map(function($review) {
                $formattedDate = Carbon::parse($review->created_at)->format('H:i d-m-Y');
                $review->formatted_created_at = $formattedDate;

                $product_id = $review->product_id;
                $order_id = $review->order_id;
                $order = Order::select(['id', 'created_at'])->with(['products' => function ($query) use ($product_id) {
                    $query->where('products.id', $product_id);
                }])->findOrFail($order_id);
                $formattedDateOrder = Carbon::parse($order->created_at)->format('H:i d-m-Y');
                $review->formatted_created_at_Order = $formattedDateOrder;

                $product = $order->products[0];
                $colorProduct = ColorProduct::with(['images'])->where('product_id', $product_id)->where('color_id', $product->pivot->color_id)->first();
                $product->imageUrl = $colorProduct->images[0]->url;
                $product->color = Color::findOrFail($product->pivot->color_id)->name;
                $product->size = Size::findOrFail($product->pivot->size_id)->name;
                if($order){
                    $review->product = $product;
                }
                return $review;
            });

            return response()->json(['reviews' => $reviews], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Review not found', 'message' => $e->getMessage()], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'review' => 'sometimes|required|string',
                'rating' => 'sometimes|required|integer|min:1|max:5',
                'images' => 'sometimes|array',
                'images.*.url' => 'sometimes|required|url'
            ]);

            $review = Review::findOrFail($id);

            DB::beginTransaction();

            $review->update($request->only(['review', 'rating']));

            if ($request->has('images')) {
                // Xóa các ảnh hiện có
                ImageReview::where('review_id', $review->id)->delete();

                // Thêm các ảnh mới
                foreach ($request->images as $image) {
                    ImageReview::create([
                        'url' => $image['url'],
                        'review_id' => $review->id,
                    ]);
                }
            }

            DB::commit();

            return response()->json(['message' => 'Review updated successfully', 'review' => $review], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update review', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $review = Review::findOrFail($id);
            $review->delete();
            return response()->json(['message' => 'Review deleted successfully'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete review', 'message' => $e->getMessage()], 500);
        }
    }
    public function destroyFromUser(Request $request, $id)
    {
        try {
            $userId = $request->user()->id;
            $review = Review::findOrFail($id);
            if($userId == $review->user_id){
                $review->delete();
                return response()->json(['message' => 'Review deleted successfully'], 200);
            }
            return response()->json(['error' => 'Failed to delete review'], 500);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete review', 'message' => $e->getMessage()], 500);
        }
    }
}