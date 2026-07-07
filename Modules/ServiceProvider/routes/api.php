<?php

use Illuminate\Support\Facades\Route;
use Modules\ServiceProvider\Http\Controllers\ServiceProviderController;

Route::prefix('v1')->group(function () {
    Route::get('service-providers', [ServiceProviderController::class, 'index']);
});
