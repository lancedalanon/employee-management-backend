<?php

use App\Http\Controllers\v1\AuthenticationController;
use App\Http\Controllers\v1\DtrController;
use App\Http\Controllers\v1\RegistrationController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('login', [AuthenticationController::class, 'login'])->name('login');
Route::post('register', [RegistrationController::class, 'register'])->name('register');
Route::post('register/company-admin', [RegistrationController::class, 'registerCompanyAdmin'])->name('register.company-admin');

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthenticationController::class, 'logout'])->name('logout');

    Route::prefix('dtrs')->name('dtrs.')->group(function () {
        Route::get('/', [DtrController::class, 'index'])->name('index');
        Route::get('{dtrId}', [DtrController::class, 'show'])->name('show');
        Route::post('time-in', [DtrController::class, 'storeTimeIn'])->name('storeTimeIn');
        Route::post('time-out', [DtrController::class, 'storeTimeOut'])->name('storeTimeOut');
        Route::post('break', [DtrController::class, 'storeBreak'])->name('storeBreak');
        Route::post('resume', [DtrController::class, 'storeResume'])->name('storeResume');
        Route::put('time-out', [DtrController::class, 'updateTimeOut'])->name('updateTimeOut');
    });
});
