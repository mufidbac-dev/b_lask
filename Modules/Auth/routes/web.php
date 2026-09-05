<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;

Route::view('password/reset/{token}', 'auth::password-reset')
    ->middleware('guest')
    ->name('password.reset');

Route::get('auth/verify-email/{id}/{hash}', [
    AuthController::class,
    'verifyEmail',
])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
