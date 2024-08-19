<?php

use App\Http\Controllers\v1\AuthenticationController;
use App\Http\Controllers\v1\RegistrationController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('login', [AuthenticationController::class, 'login'])->name('login');
Route::post('register', [RegistrationController::class, 'register'])->name('register');
Route::post('register/company-admin', [RegistrationController::class, 'registerCompanyAdmin'])->name('register.company-admin');

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthenticationController::class, 'logout'])->name('logout');
});
