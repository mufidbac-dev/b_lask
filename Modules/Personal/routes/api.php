<?php

use Illuminate\Support\Facades\Route;
use Modules\Personal\Http\Controllers\CalendarEventController;
use Modules\Personal\Http\Controllers\NoteController;
use Modules\Personal\Http\Controllers\ProjectController;
use Modules\Personal\Http\Controllers\ReminderController;
use Modules\Personal\Http\Controllers\TagController;
use Modules\Personal\Http\Controllers\TaskController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function (): void {
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('tasks', TaskController::class);
    Route::apiResource('tags', TagController::class);
    Route::apiResource('notes', NoteController::class);
    Route::apiResource('reminders', ReminderController::class);
    Route::apiResource('calendar-events', CalendarEventController::class);
});
