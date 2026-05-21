<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

Route::post('/auth/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Product Routes
|--------------------------------------------------------------------------
*/
Route::prefix('products')->group(function () {

    // Public: read-only
    Route::get('/',    [ProductController::class, 'index']);
    Route::get('/{id}', [ProductController::class, 'show']);

    // Protected: write — requires auth + rate limit (1x per 5 seconds)
    Route::middleware(['auth:sanctum', 'throttle:product-write'])->group(function () {
        Route::post('/',       [ProductController::class, 'store']);
        Route::put('/{id}',    [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });
});
