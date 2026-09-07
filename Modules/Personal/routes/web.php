<?php

use Illuminate\Support\Facades\Route;
use Modules\Personal\Http\Controllers\PersonalController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('personals', PersonalController::class)->names('personal');
});
