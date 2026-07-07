<?php

use Illuminate\Support\Facades\Route;
use Modules\Appointment\Http\Controllers\AppointmentController;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('appointments/get', [AppointmentController::class, 'index']);
    Route::post('appointments/cancel', [AppointmentController::class, 'cancel']);
    Route::post('appointments/reschedule', [AppointmentController::class, 'reschedule']);
    Route::post('appointments/book-again', [AppointmentController::class, 'bookAgain']);
});
