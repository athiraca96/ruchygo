<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\AddToCartRequest;
use App\Http\Requests\Customer\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function index(): JsonResponse
    {
        $cart = $this->getOrCreateCart();
        $cart->load(['items.product.images', 'items.product.category']);

        return response()->json([
            'success' => true,
            'data' => new CartResource($cart),
        ]);
    }

    public function add(AddToCartRequest $request): JsonResponse
    {
        $product = Product::find($request->product_id);

        if (!$product || !$product->is_active || !$product->is_approved) {
            return response()->json([
                'success' => false,
                'message' => 'Product is not available',
            ], 422);
        }

        $quantity = $request->input('quantity', 1);

        if ($product->stock_quantity < $quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock available',
            ], 422);
        }

        $cart = $this->getOrCreateCart();

        $cartItem = $cart->items()->where('product_id', $product->id)->first();

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $quantity;
            if ($product->stock_quantity < $newQuantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot add more items. Insufficient stock.',
                ], 422);
            }
            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
            ]);
        }

        $cart->load(['items.product.images', 'items.product.category']);

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart',
            'data' => new CartResource($cart),
        ]);
    }

    public function update(UpdateCartItemRequest $request, CartItem $cartItem): JsonResponse
    {
        $cart = $this->getOrCreateCart();

        if ($cartItem->cart_id !== $cart->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found',
            ], 404);
        }

        $product = $cartItem->product;

        if ($product->stock_quantity < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock available',
            ], 422);
        }

        $cartItem->update(['quantity' => $request->quantity]);

        $cart->load(['items.product.images', 'items.product.category']);

        return response()->json([
            'success' => true,
            'message' => 'Cart updated',
            'data' => new CartResource($cart),
        ]);
    }

    public function removeItem(CartItem $cartItem): JsonResponse
    {
        $cart = $this->getOrCreateCart();

        if ($cartItem->cart_id !== $cart->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found',
            ], 404);
        }

        $cartItem->delete();

        $cart->load(['items.product.images', 'items.product.category']);

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart',
            'data' => new CartResource($cart),
        ]);
    }

    public function clear(): JsonResponse
    {
        $cart = auth()->user()->cart;

        if ($cart) {
            $cart->clear();
        }

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared',
        ]);
    }

    protected function getOrCreateCart(): Cart
    {
        $user = auth()->user();

        if (!$user->cart) {
            $user->cart()->create();
            $user->refresh();
        }

        return $user->cart;
    }
}
