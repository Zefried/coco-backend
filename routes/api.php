<?php

use App\Http\Controllers\AdminAuth\AdminAuthController;
use App\Http\Controllers\AdminOrders\AdminOrderController;
use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\Checkout\CheckoutController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\SubCategory\SubCategoryController;
use App\Http\Controllers\UserAuth\UserAuthController;
use App\Http\Middleware\AdminCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



// These are protected admin routes not for website user

Route::middleware(['auth:sanctum', AdminCheck::class])->prefix('admin')->group(function () {
    Route::post('/category/resource', [CategoryController::class, 'resource']);
    Route::delete('/delete-category/{id}', [CategoryController::class, 'deleteCategory']);
    Route::post('/sub-category/resource', [SubCategoryController::class, 'resource']);

    // Read and fetch 
    Route::get('/fetch-all-category', [CategoryController::class, 'fetchCategory']);
    Route::post('/fetch-sub-category', [SubCategoryController::class, 'fetchSubCategory']);

    Route::post('/add-product', [ProductController::class, 'store']);
    Route::get('/fetch-products/category', [ProductController::class, 'viewProducts']);
    Route::get('/fetch-products/subcategory', [ProductController::class, 'viewProducts']);
    Route::post('/product/{id}/status', [ProductController::class, 'updateBestSeller']);
    Route::get('/products/search', [ProductController::class, 'searchProducts']);
    Route::get('/products/search/{id}', [ProductController::class, 'searchProductsById']);

    // Edit products
    Route::post('/product/image/{id}', [ProductController::class, 'productImageChange']);
    Route::post('/product/update/{id}', [ProductController::class, 'updateProduct']);


    // Orders
    Route::get('/user/orders', [AdminOrderController::class, 'fetchAllUserOrders']);
    Route::patch('/orders/{id}/status', [AdminOrderController::class, 'updateOrderStatus']);
    Route::get('/orders/search', [AdminOrderController::class, 'searchOrders']); // all orders
    Route::get('/orders/search/{orderId}', [AdminOrderController::class, 'selectOrder']); // single orders
    Route::get('/orders/info/{orderId}', [AdminOrderController::class, 'findFullInfo']); // order full info

    // Reports
    Route::post('/reports', [ReportController::class, 'getReport']);
    Route::get('/orders/pending', [ReportController::class, 'fetchPendingOrders']);
    Route::get('/orders/shipped', [ReportController::class, 'fetchShippedOrders']);
    Route::get('/orders/completed', [ReportController::class, 'fetchCompletedOrders']);
    Route::get('/orders/total', [ReportController::class, 'fetchTotalOrders']);

});


// These are protected user routes not for website user
Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::post('/add-user-cart', [CheckoutController::class, 'addUserCart']);
    Route::post('/remove-cart-item', [CheckoutController::class, 'removeCartItem']);
    Route::post('/update-cart-quantity', [CheckoutController::class, 'updateCartQuantity']);
    Route::post('/cart', [CheckoutController::class, 'userCart']);
  

    Route::post('/checkout-items', [CheckoutController::class, 'getCheckoutItems']);
    Route::post('/checkout', [CheckoutController::class, 'checkout']);

    Route::get('/orders', [CheckoutController::class, 'userOrders']);
});



// public routes
Route::post('/auth-resource', [AdminAuthController::class, 'resource']);
Route::post('/user-register', [UserAuthController::class, 'userRegister']);
Route::post('/user-login', [UserAuthController::class, 'userLogin']);

// Public route for fetching products by static category title
Route::get('/categories', [CategoryController::class, 'fetchCategory']);
Route::post('/fetch-products/static-categories', [ProductController::class, 'fetchByStaticCategory']);
Route::get('/product/{id}', [ProductController::class, 'fetchSingleProduct']);
Route::post('/fetch-products', [ProductController::class, 'fetchMultipleProducts']);
Route::get('/best-sellers', [ProductController::class, 'fetchBestSellers']);


