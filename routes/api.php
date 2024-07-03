<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DtrController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('login', [AuthController::class, 'login']);

// Protected routes (authenticated with Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'showAuthenticatedUser'])->name('users.show');
        Route::put('/', [UserController::class, 'updatePersonalInformation'])->name('users.update');
    });

    Route::prefix('dtr')->group(function () {
        Route::get('/', [DtrController::class, 'getDtr'])->name('dtr.getDtr');
        Route::post('/time-in', [DtrController::class, 'timeIn'])->name('dtr.timeIn');
        Route::post('/break/{dtr}', [DtrController::class, 'break'])->name('dtr.break');
        Route::post('/resume/{dtr}', [DtrController::class, 'resume'])->name('dtr.resume');
        Route::post('/time-out/{dtr}', [DtrController::class, 'timeOut'])->name('dtr.timeOut');
    });

    Route::post('logout', [AuthController::class, 'logout']);
});
