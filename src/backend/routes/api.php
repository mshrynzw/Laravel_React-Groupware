<?php

use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\ApprovalRuleController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WorkflowRequestController;
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

    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn']);
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut']);
    Route::get('/attendance/records', [AttendanceController::class, 'records']);
    Route::get('/attendance/summary', [AttendanceController::class, 'summary']);

    Route::get('/requests', [WorkflowRequestController::class, 'index']);
    Route::post('/requests', [WorkflowRequestController::class, 'store']);
    Route::get('/requests/{requestModel}', [WorkflowRequestController::class, 'show']);
    Route::post('/requests/{requestModel}/submit', [WorkflowRequestController::class, 'submit']);
    Route::post('/requests/{requestModel}/approve', [WorkflowRequestController::class, 'approve']);
    Route::post('/requests/{requestModel}/reject', [WorkflowRequestController::class, 'reject']);
    Route::get('/requests/{requestModel}/resolution', [WorkflowRequestController::class, 'resolution']);

    Route::get('/workflow/rules', [ApprovalRuleController::class, 'index']);
    Route::post('/workflow/rules', [ApprovalRuleController::class, 'store']);
    Route::patch('/workflow/rules/{rule}', [ApprovalRuleController::class, 'update']);
    Route::post('/workflow/rules/{rule}/publish', [ApprovalRuleController::class, 'publish']);
    Route::post('/workflow/rules/{rule}/activate', [ApprovalRuleController::class, 'activate']);
    Route::post('/workflow/rules/{rule}/deactivate', [ApprovalRuleController::class, 'deactivate']);

    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::get('/announcements/{announcement}', [AnnouncementController::class, 'show']);
    Route::get('/admin/announcements', [AnnouncementController::class, 'adminIndex']);
    Route::post('/announcements', [AnnouncementController::class, 'store']);
    Route::patch('/announcements/{announcement}', [AnnouncementController::class, 'update']);
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update']);
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy']);

    Route::get('/files', [FileController::class, 'index']);
    Route::post('/files', [FileController::class, 'store']);
    Route::get('/files/{stored_file}/download', [FileController::class, 'download'])->whereNumber('stored_file');
    Route::delete('/files/{stored_file}', [FileController::class, 'destroy'])->whereNumber('stored_file');
});
