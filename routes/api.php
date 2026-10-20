<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);

        Route::middleware('auth:api')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('refresh', [AuthController::class, 'refresh']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    Route::middleware('auth:api')->group(function () {
        Route::apiResource('orders', OrderController::class);

        Route::get('payments', [PaymentController::class, 'index']);
        Route::get('orders/{order}/payments', [PaymentController::class, 'indexForOrder']);
        Route::post('orders/{order}/payments', [PaymentController::class, 'store']);
    });
});
