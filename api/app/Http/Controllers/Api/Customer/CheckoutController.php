<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\PlatformSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function __construct(
        protected PlatformSettingService $settingService
    ) {}

    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $user = auth()->user();
        $cart = $user->cart;

        if (!$cart || $cart->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Your cart is empty',
            ], 422);
        }

        // Validate address belongs to user
        $address = Address::where('id', $request->address_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid delivery address',
            ], 422);
        }

        // Validate all products are available
        $cart->load('items.product');
        foreach ($cart->items as $item) {
            if (!$item->product->is_active || !$item->product->is_approved) {
                return response()->json([
                    'success' => false,
                    'message' => "Product '{$item->product->name}' is no longer available",
                ], 422);
            }

            if ($item->product->stock_quantity < $item->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient stock for '{$item->product->name}'",
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $subtotal = $cart->total;
            $platformFee = $this->settingService->calculatePlatformFee($subtotal);
            $shippingFee = $this->settingService->calculateShippingFee($subtotal);
            $total = $subtotal + $platformFee + $shippingFee;

            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'platform_fee' => $platformFee,
                'shipping_fee' => $shippingFee,
                'total' => $total,
                'status' => 'pending',
                'shipping_address' => $address->toArray(),
                'payment_method' => $request->input('payment_method', 'cod'),
                'payment_status' => 'pending',
            ]);

            // Create order items and update stock
            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'vendor_id' => $item->product->vendor_id,
                    'product_name' => $item->product->name,
                    'product_price' => $item->product->getEffectivePrice(),
                    'quantity' => $item->quantity,
                    'total' => $item->product->getEffectivePrice() * $item->quantity,
                    'status' => 'pending',
                ]);

                // Reduce stock
                $item->product->decrement('stock_quantity', $item->quantity);
            }

            // Clear cart
            $cart->clear();

            DB::commit();

            $order->load(['items.product', 'items.vendor']);

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'data' => new OrderResource($order),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to place order. Please try again.',
            ], 500);
        }
    }

    public function preview(): JsonResponse
    {
        $user = auth()->user();
        $cart = $user->cart;

        if (!$cart || $cart->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Your cart is empty',
            ], 422);
        }

        $cart->load('items.product');
        $subtotal = $cart->total;
        $platformFee = $this->settingService->calculatePlatformFee($subtotal);
        $shippingFee = $this->settingService->calculateShippingFee($subtotal);
        $total = $subtotal + $platformFee + $shippingFee;

        return response()->json([
            'success' => true,
            'data' => [
                'subtotal' => round($subtotal, 2),
                'platform_fee' => round($platformFee, 2),
                'shipping_fee' => round($shippingFee, 2),
                'total' => round($total, 2),
                'free_shipping_threshold' => $this->settingService->getFreeShippingThreshold(),
                'amount_for_free_shipping' => max(0, $this->settingService->getFreeShippingThreshold() - $subtotal),
            ],
        ]);
    }
}
