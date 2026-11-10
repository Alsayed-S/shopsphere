<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Modules\Catalog\Controllers\CategoryController;
use App\Http\Controllers\Modules\Catalog\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'ShopSphere API is running',
        'data' => [
            'application' => 'ShopSphere',
            'version' => '1.0.0',
        ],
    ]);
});

// products
Route::post('/products', [ProductController::class, 'store']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{productId}', [ProductController::class, 'show']);
Route::put('/products/{productId}', [ProductController::class, 'update']);
Route::delete('/products/{productId}', [ProductController::class, 'destroy']);

// categories
Route::get('/categories', [CategoryController::class, 'index']);
Route::post('/categories', [CategoryController::class, 'store']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::put('/categories/{id}', [CategoryController::class, 'update']);
Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:3,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
