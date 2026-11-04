<?php

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
