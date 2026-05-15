<?php

use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\ApprovalRuleController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatMessageController;
use App\Http\Controllers\Api\ChatRoomController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\PayrollRunController;
use App\Http\Controllers\Api\PayrollSlipController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WikiPageController;
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

    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::patch('/tasks/{task}', [TaskController::class, 'update']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);

    Route::get('/schedules', [ScheduleController::class, 'index']);
    Route::post('/schedules', [ScheduleController::class, 'store']);
    Route::get('/schedules/{schedule}', [ScheduleController::class, 'show']);
    Route::patch('/schedules/{schedule}', [ScheduleController::class, 'update']);
    Route::put('/schedules/{schedule}', [ScheduleController::class, 'update']);
    Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy']);

    Route::get('/chat/rooms', [ChatRoomController::class, 'index']);
    Route::post('/chat/rooms', [ChatRoomController::class, 'store']);
    Route::get('/chat/rooms/{chat_room}/messages', [ChatMessageController::class, 'index']);
    Route::post('/chat/rooms/{chat_room}/messages', [ChatMessageController::class, 'store']);

    Route::get('/wiki/pages', [WikiPageController::class, 'index']);
    Route::get('/wiki/pages/tree', [WikiPageController::class, 'tree']);
    Route::get('/wiki/pages/by-slug/{slug}', [WikiPageController::class, 'showBySlug'])
        ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');
    Route::get('/wiki/pages/{wiki_page}/revisions', [WikiPageController::class, 'revisions']);
    Route::get('/wiki/pages/{wiki_page}/revisions/{wiki_revision}', [WikiPageController::class, 'showRevision']);
    Route::post('/wiki/pages', [WikiPageController::class, 'store']);
    Route::put('/wiki/pages/{wiki_page}', [WikiPageController::class, 'update']);
    Route::delete('/wiki/pages/{wiki_page}', [WikiPageController::class, 'destroy']);

    Route::get('/search', [SearchController::class, 'index']);

    Route::post('/payroll/runs', [PayrollRunController::class, 'store']);
    Route::get('/payroll/runs/{payroll_run}', [PayrollRunController::class, 'show']);
    Route::get('/payroll/slips', [PayrollSlipController::class, 'index']);
    Route::get('/payroll/slips/{payroll_slip}', [PayrollSlipController::class, 'show']);
    Route::get('/payroll/slips/{payroll_slip}/download', [PayrollSlipController::class, 'download']);
});
