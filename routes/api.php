<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('users/me', [UserController::class, 'showAuthenticatedUser']);
    Route::put('users/personal-information', [UserController::class, 'updatePersonalInformation']);
});
