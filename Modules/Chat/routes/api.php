<?php

use Illuminate\Support\Facades\Route;
use Modules\Chat\Http\Controllers\AdminSupportController;
use Modules\Chat\Http\Controllers\DoctorChatController;
use Modules\Chat\Http\Controllers\PatientChatController;
use Modules\Chat\Http\Controllers\PatientSupportController;

Route::prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum', 'role:patient'])->group(function () {
        Route::get('chat/conversations', [PatientChatController::class, 'conversations']);
        Route::post('chat/conversations', [PatientChatController::class, 'storeConversation']);
        Route::get('chat/messages', [PatientChatController::class, 'messages']);
        Route::post('chat/send-message', [PatientChatController::class, 'sendMessage'])
            ->middleware('throttle:30,1');
        Route::post('chat/mark-read', [PatientChatController::class, 'markRead']);

        Route::get('support/messages', [PatientSupportController::class, 'messages']);
        Route::post('support/send-message', [PatientSupportController::class, 'sendMessage'])
            ->middleware('throttle:30,1');
        Route::post('support/mark-read', [PatientSupportController::class, 'markRead']);
    });

    Route::middleware(['auth:sanctum', 'role:doctor'])->prefix('doctor/chat')->group(function () {
        Route::get('conversations', [DoctorChatController::class, 'conversations']);
        Route::get('messages', [DoctorChatController::class, 'messages']);
        Route::post('send-message', [DoctorChatController::class, 'sendMessage'])
            ->middleware('throttle:30,1');
        Route::post('mark-read', [DoctorChatController::class, 'markRead']);
    });

    Route::middleware(['auth:sanctum', 'role:admin,support'])->prefix('admin/support')->group(function () {
        Route::get('threads', [AdminSupportController::class, 'threads']);
        Route::get('messages', [AdminSupportController::class, 'messages']);
        Route::post('send-message', [AdminSupportController::class, 'sendMessage'])
            ->middleware('throttle:30,1');
        Route::post('mark-read', [AdminSupportController::class, 'markRead']);
    });
});
