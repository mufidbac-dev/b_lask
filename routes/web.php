<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


// API Documentation
Route::get('/docs', function () {
    return view('request-docs::index');
})->name('api.docs');
