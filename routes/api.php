<?php

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

Route::post('/products', [ProductController::class, 'store']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{productId}', [ProductController::class, 'show']);
Route::put('/products/{productId}', [ProductController::class, 'update']);
Route::delete('/products/{productId}', [ProductController::class, 'destroy']);
