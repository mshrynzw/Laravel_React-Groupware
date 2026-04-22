<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/register', [AuthController::class, 'register'])->middleware('guest');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('guest');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('guest');

Route::get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/email/verification-notification', [AuthController::class, 'sendVerificationNotification'])
        ->middleware('throttle:6,1');

    Route::apiResource('users', UserController::class);
    Route::apiResource('groups', GroupController::class);
});
