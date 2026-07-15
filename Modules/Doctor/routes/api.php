<?php

use Illuminate\Support\Facades\Route;
use Modules\Doctor\Http\Controllers\DoctorAnalyticsController;
use Modules\Doctor\Http\Controllers\DoctorAppointmentController;
use Modules\Doctor\Http\Controllers\DoctorConsultationController;
use Modules\Doctor\Http\Controllers\DoctorDashboardController;
use Modules\Doctor\Http\Controllers\DoctorMarketingController;
use Modules\Doctor\Http\Controllers\DoctorNotificationController;
use Modules\Doctor\Http\Controllers\DoctorPatientController;
use Modules\Doctor\Http\Controllers\DoctorProfileController;
use Modules\Doctor\Http\Controllers\DoctorScheduleController;
use Modules\Doctor\Http\Controllers\DoctorSettingsController;

Route::prefix('v1')->middleware(['auth:sanctum', 'role:doctor'])->group(function () {
    Route::get('doctor/dashboard', [DoctorDashboardController::class, 'dashboard']);
    Route::get('doctor/profile/summary', [DoctorDashboardController::class, 'profileSummary']);

    Route::get('doctor/appointments', [DoctorAppointmentController::class, 'index']);
    Route::patch('doctor/appointments/{appointmentId}/status', [DoctorAppointmentController::class, 'updateStatus']);

    Route::get('doctor/patients', [DoctorPatientController::class, 'index']);
    Route::get('doctor/patients/{patientId}', [DoctorPatientController::class, 'show']);

    Route::post('doctor/consultation', [DoctorConsultationController::class, 'store']);

    Route::get('doctor/schedule', [DoctorScheduleController::class, 'show']);
    Route::put('doctor/schedule', [DoctorScheduleController::class, 'update']);

    Route::get('doctor/notifications', [DoctorNotificationController::class, 'index']);
    Route::patch('doctor/notifications/{notificationId}/read', [DoctorNotificationController::class, 'markRead']);

    Route::get('doctor/profile', [DoctorProfileController::class, 'show']);
    Route::put('doctor/profile', [DoctorProfileController::class, 'update']);
    Route::post('doctor/clinics', [DoctorProfileController::class, 'storeClinic']);
    Route::put('doctor/clinics/{clinicId}', [DoctorProfileController::class, 'updateClinic']);
    Route::put('doctor/services', [DoctorProfileController::class, 'updateServices']);
    Route::put('doctor/payment-details', [DoctorProfileController::class, 'updatePaymentDetails']);

    Route::get('doctor/analytics', [DoctorAnalyticsController::class, 'index']);

    Route::get('doctor/marketing/packages', [DoctorMarketingController::class, 'packages']);
    Route::post('doctor/marketing/subscribe', [DoctorMarketingController::class, 'subscribe']);

    Route::get('doctor/settings', [DoctorSettingsController::class, 'show']);
    Route::put('doctor/settings', [DoctorSettingsController::class, 'update']);
});
