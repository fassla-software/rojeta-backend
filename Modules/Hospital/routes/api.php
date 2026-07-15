<?php

use Illuminate\Support\Facades\Route;
use Modules\Hospital\Http\Controllers\HospitalController;

Route::prefix('v1')->middleware(['auth:sanctum', 'role:hospital'])->group(function () {
    Route::get('hospital/dashboard', [HospitalController::class, 'dashboard']);
    Route::get('hospital/appointments', [HospitalController::class, 'appointments']);
    Route::get('hospital/services', [HospitalController::class, 'services']);
    Route::patch('hospital/services/{serviceId}', [HospitalController::class, 'updateService']);
    Route::get('hospital/specialties', [HospitalController::class, 'specialties']);
    Route::post('hospital/specialties', [HospitalController::class, 'storeSpecialty']);
    Route::post('hospital/specialties/{specialtyId}/staff', [HospitalController::class, 'storeStaff']);
    Route::get('hospital/icu-rooms', [HospitalController::class, 'icuRooms']);
    Route::get('hospital/incubators', [HospitalController::class, 'incubators']);
    Route::get('hospital/lab-tests', [HospitalController::class, 'labTests']);
    Route::get('hospital/radiology-services', [HospitalController::class, 'radiologyServices']);
    Route::get('hospital/settings', [HospitalController::class, 'settings']);
    Route::put('hospital/settings', [HospitalController::class, 'updateSettings']);
});
