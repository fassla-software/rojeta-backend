<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'store'])->name('auth.register');

    Route::post('auth/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('auth/resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('auth/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);
        Route::post('auth/login', [AuthController::class, 'login']);
        Route::post('auth/resend-otp', [AuthController::class, 'resendOtp']);

    Route::middleware(['auth:sanctum'])->group(function () {
            Route::get('/auth/profile', [AuthController::class, 'profile']);

        Route::apiResource('auths', AuthController::class)->names('auth');
    });
});
