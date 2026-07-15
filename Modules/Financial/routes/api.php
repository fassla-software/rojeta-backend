<?php

use Illuminate\Support\Facades\Route;
use Modules\Financial\Http\Controllers\FinancialController;

Route::prefix('v1')->middleware(['auth:sanctum', 'role:doctor,laboratory,radiology,nursing'])->group(function () {
    Route::get('financial/summary', [FinancialController::class, 'summary']);
    Route::get('financial/transactions', [FinancialController::class, 'transactions']);
});
