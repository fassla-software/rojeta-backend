<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\Http\Controllers\NotificationController;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('notifications/get', [NotificationController::class, 'index']);
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    Route::post('notifications/mark-read', [NotificationController::class, 'markRead']);
    Route::post('notifications/delete', [NotificationController::class, 'destroy']);
});
