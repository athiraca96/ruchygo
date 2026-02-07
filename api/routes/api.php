<?php

use Illuminate\Support\Facades\Route;

// Auth Controllers
use App\Http\Controllers\Api\Auth\AdminAuthController;
use App\Http\Controllers\Api\Auth\OtpAuthController;

// Admin Controllers
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\SubcategoryController as AdminSubcategoryController;
use App\Http\Controllers\Api\Admin\VendorController as AdminVendorController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Admin\PlatformSettingController;
use App\Http\Controllers\Api\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Api\Admin\ComplaintController as AdminComplaintController;
use App\Http\Controllers\Api\Admin\ReturnRequestController as AdminReturnRequestController;
use App\Http\Controllers\Api\Admin\CustomerController as AdminCustomerController;

// Vendor Controllers
use App\Http\Controllers\Api\Vendor\OnboardingController;
use App\Http\Controllers\Api\Vendor\ProductController as VendorProductController;
use App\Http\Controllers\Api\Vendor\OrderController as VendorOrderController;
use App\Http\Controllers\Api\Vendor\DashboardController as VendorDashboardController;
use App\Http\Controllers\Api\Vendor\ReviewController as VendorReviewController;
use App\Http\Controllers\Api\Vendor\ComplaintController as VendorComplaintController;

// Customer Controllers
use App\Http\Controllers\Api\Customer\ProfileController;
use App\Http\Controllers\Api\Customer\AddressController;
use App\Http\Controllers\Api\Customer\ProductController as CustomerProductController;
use App\Http\Controllers\Api\Customer\CartController;
use App\Http\Controllers\Api\Customer\CheckoutController;
use App\Http\Controllers\Api\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Api\Customer\ReviewController as CustomerReviewController;
use App\Http\Controllers\Api\Customer\ComplaintController as CustomerComplaintController;
use App\Http\Controllers\Api\Customer\ReturnRequestController as CustomerReturnRequestController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// OTP Authentication (for vendors and customers)
Route::prefix('otp')->group(function () {
    Route::post('send', [OtpAuthController::class, 'sendOtp']);
    Route::post('verify', [OtpAuthController::class, 'verifyOtp']);
});

// Admin Authentication
Route::prefix('admin')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login']);
});

// Public Product & Category Routes
Route::get('categories', [CustomerProductController::class, 'categories']);
Route::get('products', [CustomerProductController::class, 'index']);
Route::get('products/{slug}', [CustomerProductController::class, 'show']);
Route::get('products/{slug}/reviews', [CustomerProductController::class, 'reviews']);

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Auth
    Route::post('logout', [AdminAuthController::class, 'logout']);
    Route::get('profile', [AdminAuthController::class, 'profile']);

    // Categories
    Route::apiResource('categories', AdminCategoryController::class);

    // Subcategories
    Route::apiResource('subcategories', AdminSubcategoryController::class);

    // Vendors
    Route::get('vendors', [AdminVendorController::class, 'index']);
    Route::get('vendors/{vendor}', [AdminVendorController::class, 'show']);
    Route::put('vendors/{vendor}', [AdminVendorController::class, 'update']);
    Route::post('vendors/{vendor}/approve', [AdminVendorController::class, 'approve']);
    Route::post('vendors/{vendor}/reject', [AdminVendorController::class, 'reject']);

    // Products
    Route::get('products', [AdminProductController::class, 'index']);
    Route::get('products/{product}', [AdminProductController::class, 'show']);
    Route::post('products/{product}/approve', [AdminProductController::class, 'approve']);
    Route::post('products/{product}/reject', [AdminProductController::class, 'reject']);

    // Orders
    Route::get('orders', [AdminOrderController::class, 'index']);
    Route::get('orders/{order}', [AdminOrderController::class, 'show']);

    // Platform Settings
    Route::get('settings', [PlatformSettingController::class, 'index']);
    Route::put('settings', [PlatformSettingController::class, 'update']);

    // Reviews
    Route::get('reviews', [AdminReviewController::class, 'index']);
    Route::put('reviews/{review}/approve', [AdminReviewController::class, 'approve']);
    Route::delete('reviews/{review}', [AdminReviewController::class, 'destroy']);

    // Complaints
    Route::get('complaints', [AdminComplaintController::class, 'index']);
    Route::get('complaints/{complaint}', [AdminComplaintController::class, 'show']);
    Route::put('complaints/{complaint}', [AdminComplaintController::class, 'update']);

    // Return Requests
    Route::get('return-requests', [AdminReturnRequestController::class, 'index']);
    Route::get('return-requests/{returnRequest}', [AdminReturnRequestController::class, 'show']);
    Route::put('return-requests/{returnRequest}', [AdminReturnRequestController::class, 'update']);

    // Customers
    Route::get('customers', [AdminCustomerController::class, 'index']);
    Route::get('customers/{customer}', [AdminCustomerController::class, 'show']);
    Route::put('customers/{customer}/toggle-status', [AdminCustomerController::class, 'toggleStatus']);
});

/*
|--------------------------------------------------------------------------
| Vendor Routes
|--------------------------------------------------------------------------
*/

Route::prefix('vendor')->middleware(['auth:sanctum', 'role:vendor'])->group(function () {
    // Logout
    Route::post('logout', [OtpAuthController::class, 'logout']);

    // Onboarding (accessible before approval)
    Route::post('onboarding', [OnboardingController::class, 'store']);
    Route::get('onboarding/status', [OnboardingController::class, 'status']);

    // Routes requiring approved vendor status
    Route::middleware('vendor.approved')->group(function () {
        // Dashboard
        Route::get('dashboard', [VendorDashboardController::class, 'index']);

        // Products
        Route::apiResource('products', VendorProductController::class);
        Route::post('products/{product}/images', [VendorProductController::class, 'uploadImages']);
        Route::delete('products/{product}/images/{image}', [VendorProductController::class, 'deleteImage']);

        // Orders
        Route::get('orders', [VendorOrderController::class, 'index']);
        Route::get('orders/{order}', [VendorOrderController::class, 'show']);
        Route::put('order-items/{orderItem}/status', [VendorOrderController::class, 'updateItemStatus']);

        // Reviews
        Route::get('reviews', [VendorReviewController::class, 'index']);

        // Complaints
        Route::get('complaints', [VendorComplaintController::class, 'index']);
    });
});

/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
*/

Route::prefix('customer')->middleware(['auth:sanctum', 'role:customer'])->group(function () {
    // Logout
    Route::post('logout', [OtpAuthController::class, 'logout']);

    // Profile
    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);

    // Addresses
    Route::apiResource('addresses', AddressController::class);

    // Cart
    Route::get('cart', [CartController::class, 'index']);
    Route::post('cart/add', [CartController::class, 'add']);
    Route::put('cart/{cartItem}', [CartController::class, 'update']);
    Route::delete('cart/{cartItem}', [CartController::class, 'removeItem']);
    Route::delete('cart', [CartController::class, 'clear']);

    // Checkout
    Route::get('checkout/preview', [CheckoutController::class, 'preview']);
    Route::post('checkout', [CheckoutController::class, 'checkout']);

    // Orders
    Route::get('orders', [CustomerOrderController::class, 'index']);
    Route::get('orders/{order}', [CustomerOrderController::class, 'show']);
    Route::post('orders/{order}/cancel', [CustomerOrderController::class, 'cancel']);

    // Reviews
    Route::get('reviews', [CustomerReviewController::class, 'index']);
    Route::post('reviews', [CustomerReviewController::class, 'store']);

    // Complaints
    Route::get('complaints', [CustomerComplaintController::class, 'index']);
    Route::post('complaints', [CustomerComplaintController::class, 'store']);

    // Return Requests
    Route::get('return-requests', [CustomerReturnRequestController::class, 'index']);
    Route::post('return-requests', [CustomerReturnRequestController::class, 'store']);
});
