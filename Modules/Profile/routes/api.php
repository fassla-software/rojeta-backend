<?php

use Illuminate\Support\Facades\Route;
use Modules\Profile\Http\Controllers\ProfileController;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('profile/get', [ProfileController::class, 'getProfile']);
    Route::post('profile/update', [ProfileController::class, 'updateProfile']);
    Route::post('profile/upload-medical-files', [ProfileController::class, 'uploadMedicalFiles']);
    Route::post('profile/help-support', [ProfileController::class, 'helpSupport']);
});
