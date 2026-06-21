<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::prefix('attendance')->group(function (): void {
            Route::post('check-in', [App\Http\Controllers\Api\V1\AttendanceController::class, 'checkIn']);
            Route::post('check-out', [App\Http\Controllers\Api\V1\AttendanceController::class, 'checkOut']);
            Route::get('', [App\Http\Controllers\Api\V1\AttendanceController::class, 'index']);
            Route::get('{id}', [App\Http\Controllers\Api\V1\AttendanceController::class, 'show']);
            Route::put('{id}', [App\Http\Controllers\Api\V1\AttendanceController::class, 'adjust']);
        });
    });
});
