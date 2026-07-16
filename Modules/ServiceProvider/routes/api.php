<?php

use Illuminate\Support\Facades\Route;
use Modules\ServiceProvider\Http\Controllers\ServiceProviderController;

Route::prefix('v1')->group(function () {
    Route::get('service-providers', [ServiceProviderController::class, 'index']);
    Route::get('service-providers/{id}/availability', [ServiceProviderController::class, 'availability']);
    Route::get('service-providers/{id}/services', [ServiceProviderController::class, 'services']);
    Route::get('service-providers/{id}/reviews', [ServiceProviderController::class, 'reviews']);
});
