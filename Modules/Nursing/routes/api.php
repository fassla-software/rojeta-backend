<?php

use Illuminate\Support\Facades\Route;
use Modules\Nursing\Http\Controllers\NursingController;

Route::prefix('v1')->middleware(['auth:sanctum', 'role:nursing'])->group(function () {
    Route::get('nursing/dashboard', [NursingController::class, 'dashboard']);
    Route::get('nursing/services', [NursingController::class, 'services']);
    Route::post('nursing/services', [NursingController::class, 'storeService']);
    Route::get('nursing/staff', [NursingController::class, 'staff']);
    Route::post('nursing/staff', [NursingController::class, 'storeStaff']);
    Route::get('nursing/profile', [NursingController::class, 'profile']);
    Route::get('nursing/office-info', [NursingController::class, 'officeInfo']);
    Route::put('nursing/office-info', [NursingController::class, 'updateOfficeInfo']);
    Route::get('nursing/working-hours', [NursingController::class, 'workingHours']);
    Route::put('nursing/working-hours', [NursingController::class, 'updateWorkingHours']);
    Route::get('nursing/settings', [NursingController::class, 'settings']);
    Route::put('nursing/settings', [NursingController::class, 'updateSettings']);
});
