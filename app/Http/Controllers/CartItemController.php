<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CartItemController extends Controller
{
    public function index()
    {
        try {
            $cartItems = CartItem::with('products')->get();
            return response()->json($cartItems);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to retrieve cart items'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'products' => 'required|array',
                'products.*.id' => 'required|exists:products,id',
                'products.*.quantity' => 'required|integer|min:1',
            ]);

            $cartItem = CartItem::create([
                'user_id' => $validated['user_id'],
            ]);

            foreach ($validated['products'] as $product) {
                $cartItem->products()->attach($product['id'], ['quantity' => $product['quantity']]);
            }

            return response()->json($cartItem->load('products'), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to create cart item'], 500);
        }
    }

    public function show($id)
    {
        try {
            $cartItem = CartItem::with('products')->findOrFail($id);
            return response()->json($cartItem);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Cart item not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to retrieve cart item'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'sometimes|required|exists:users,id',
                'products' => 'sometimes|array',
                'products.*.id' => 'required_with:products|exists:products,id',
                'products.*.quantity' => 'required_with:products|integer|min:1',
            ]);

            $cartItem = CartItem::findOrFail($id);
            $cartItem->update($validated);

            if (isset($validated['products'])) {
                $cartItem->products()->detach();
                foreach ($validated['products'] as $product) {
                    $cartItem->products()->attach($product['id'], ['quantity' => $product['quantity']]);
                }
            }

            return response()->json($cartItem->load('products'));
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Cart item not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to update cart item'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $cartItem = CartItem::findOrFail($id);
            $cartItem->delete();

            return response()->json(null, 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Cart item not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to delete cart item'], 500);
        }
    }
}
