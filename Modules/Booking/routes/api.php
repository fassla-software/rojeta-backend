<?php

use Illuminate\Support\Facades\Route;
use Modules\Booking\Http\Controllers\BookingController;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('bookings', [BookingController::class, 'index']);
    Route::patch('bookings/{bookingId}/status', [BookingController::class, 'updateStatus']);
});
