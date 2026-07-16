<?php

use Illuminate\Support\Facades\Route;
use Modules\Booking\Http\Controllers\BookingController;
use Modules\Booking\Http\Controllers\PatientBookingController;
use Illuminate\Http\Request;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('bookings', function (Request $request) {
        if ($request->user()->role === 'patient') {
            return app(PatientBookingController::class)->index($request);
        }
        return app(BookingController::class)->index($request);
    });
    
    Route::post('bookings', [PatientBookingController::class, 'store']);
    Route::get('bookings/{id}', [PatientBookingController::class, 'show']);
    Route::patch('bookings/{id}/cancel', [PatientBookingController::class, 'cancel']);
    Route::patch('bookings/{id}/reschedule', [PatientBookingController::class, 'reschedule']);
    Route::post('bookings/{id}/reviews', [PatientBookingController::class, 'submitReview']);

    Route::patch('bookings/{bookingId}/status', [BookingController::class, 'updateStatus']);
});
