<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\TransactionApiController;
use Illuminate\Support\Facades\Route;

// Public API routes
Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    // Protected API routes via Sanctum
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // Dashboard Summary Endpoint
        Route::get('/dashboard/summary', [DashboardApiController::class, 'summary']);

        // Transactions Endpoints
        Route::get('/transactions', [TransactionApiController::class, 'index']);
        Route::post('/transactions', [TransactionApiController::class, 'store']);
    });
});
