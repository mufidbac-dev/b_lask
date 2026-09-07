<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReceiptController;

Route::get('/', fn () => view('welcome'));

Route::middleware('auth')->group(function (): void {
    Route::get('/receipts/create', [ReceiptController::class, 'create'])->name('receipts.create');
    Route::post('/receipts', [ReceiptController::class, 'store'])->name('receipts.store');
    Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');
    Route::post('/receipts/{receipt}/confirm', [ReceiptController::class, 'confirm'])->name('receipts.confirm');
});


// API Documentation
Route::get('/docs', function () {
    return view('request-docs::index');
})->name('api.docs');
