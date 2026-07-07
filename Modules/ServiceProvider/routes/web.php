<?php

use Illuminate\Support\Facades\Route;
use Modules\ServiceProvider\Http\Controllers\ServiceProviderController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('serviceproviders', ServiceProviderController::class)->names('serviceprovider');
});
