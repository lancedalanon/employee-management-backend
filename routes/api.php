<?php

use App\Http\Controllers\AuthController;
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

    Route::post('logout', [AuthController::class, 'logout']);
});
