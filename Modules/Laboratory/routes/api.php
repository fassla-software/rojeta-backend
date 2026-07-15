<?php

use Illuminate\Support\Facades\Route;
use Modules\Laboratory\Http\Controllers\LaboratoryController;

Route::prefix('v1')->middleware(['auth:sanctum', 'role:laboratory,radiology'])->group(function () {
    Route::get('lab/dashboard', [LaboratoryController::class, 'dashboard']);
    Route::get('lab/services', [LaboratoryController::class, 'services']);
    Route::post('lab/services', [LaboratoryController::class, 'storeService']);
    Route::get('lab/branches', [LaboratoryController::class, 'branches']);
    Route::post('lab/branches', [LaboratoryController::class, 'storeBranch']);
    Route::get('lab/home-visits/config', [LaboratoryController::class, 'homeVisitConfig']);
    Route::put('lab/home-visits/config', [LaboratoryController::class, 'updateHomeVisitConfig']);
    Route::get('lab/profile', [LaboratoryController::class, 'profile']);
    Route::get('lab/info', [LaboratoryController::class, 'info']);
    Route::put('lab/info', [LaboratoryController::class, 'updateInfo']);
    Route::get('lab/working-hours', [LaboratoryController::class, 'workingHours']);
    Route::put('lab/working-hours', [LaboratoryController::class, 'updateWorkingHours']);
    Route::get('lab/settings', [LaboratoryController::class, 'settings']);
    Route::put('lab/settings', [LaboratoryController::class, 'updateSettings']);
});
