<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $vendorId = auth()->id();

        // Products stats
        $totalProducts = Product::forVendor($vendorId)->count();
        $activeProducts = Product::forVendor($vendorId)->active()->approved()->count();
        $pendingProducts = Product::forVendor($vendorId)->where('is_approved', false)->count();

        // Orders stats
        $orderItems = OrderItem::forVendor($vendorId);
        $totalOrders = $orderItems->clone()->distinct('order_id')->count('order_id');
        $pendingOrders = $orderItems->clone()->where('status', 'pending')->count();
        $processingOrders = $orderItems->clone()->where('status', 'processing')->count();
        $completedOrders = $orderItems->clone()->where('status', 'delivered')->count();

        // Revenue
        $totalRevenue = $orderItems->clone()->where('status', 'delivered')->sum('total');
        $thisMonthRevenue = $orderItems->clone()
            ->where('status', 'delivered')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');

        // Reviews
        $productIds = Product::forVendor($vendorId)->pluck('id');
        $avgRating = Review::whereIn('product_id', $productIds)
            ->where('is_approved', true)
            ->avg('rating');
        $totalReviews = Review::whereIn('product_id', $productIds)
            ->where('is_approved', true)
            ->count();

        // Recent orders
        $recentOrders = OrderItem::forVendor($vendorId)
            ->with(['order.user', 'product'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'order_number' => $item->order->order_number,
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'total' => $item->total,
                    'status' => $item->status,
                    'customer' => $item->order->user->name ?? $item->order->user->phone,
                    'created_at' => $item->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'products' => [
                    'total' => $totalProducts,
                    'active' => $activeProducts,
                    'pending_approval' => $pendingProducts,
                ],
                'orders' => [
                    'total' => $totalOrders,
                    'pending' => $pendingOrders,
                    'processing' => $processingOrders,
                    'completed' => $completedOrders,
                ],
                'revenue' => [
                    'total' => round($totalRevenue, 2),
                    'this_month' => round($thisMonthRevenue, 2),
                ],
                'reviews' => [
                    'average_rating' => $avgRating ? round($avgRating, 1) : null,
                    'total' => $totalReviews,
                ],
                'recent_orders' => $recentOrders,
            ],
        ]);
    }
}
