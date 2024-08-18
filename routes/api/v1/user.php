<?php

use App\Http\Controllers\v1\AuthenticationController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('login', [AuthenticationController::class, 'login'])->name('login');

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthenticationController::class, 'logout'])->name('logout');
});
