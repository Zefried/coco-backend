<?php

use App\Http\Controllers\AdminAuth\AdminAuthController;
use App\Http\Controllers\Category\CategoryController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\SubCategory\SubCategoryController;
use App\Http\Middleware\AdminCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/auth-resource', [AdminAuthController::class, 'resource']);

Route::middleware(['auth:sanctum', AdminCheck::class])->prefix('admin')->group(function () {
    Route::post('/category/resource', [CategoryController::class, 'resource']);
    Route::post('/sub-category/resource', [SubCategoryController::class, 'resource']);

    // Read and fetch 
    Route::get('/fetch-all-category', [CategoryController::class, 'fetchCategory']);
    Route::post('/fetch-sub-category', [SubCategoryController::class, 'fetchSubCategory']);

    Route::post('/add-product', [ProductController::class, 'store']);
    Route::post('/fetch-products/category', [ProductController::class, 'viewProducts']);
    Route::post('/fetch-products/subcategory', [ProductController::class, 'viewProducts']);
    Route::get('/product/{id}', [ProductController::class, 'fetchSingleProduct']);
});

// Public route for fetching products by static category title
Route::post('/fetch-products/static-categories', [ProductController::class, 'fetchByStaticCategory']);




