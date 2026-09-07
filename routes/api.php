<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionSummaryController;

Route::middleware('auth:sanctum')->get('/v1/transactions/summary', TransactionSummaryController::class);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
