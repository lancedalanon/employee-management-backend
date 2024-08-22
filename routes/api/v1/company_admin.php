<?php

use App\Http\Controllers\v1\RegistrationController;
use Illuminate\Support\Facades\Route;

// Authenticated routes for company admin
Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:company_admin')->prefix('company-admin')->name('companyAdmin.')->group(function () {
        Route::post('send-invite', [RegistrationController::class, 'sendInvite'])->name('sendInvite');
    });
});
