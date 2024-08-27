<?php

use App\Http\Controllers\v1\CompanyAdmin\ProjectController;
use App\Http\Controllers\v1\RegistrationController;
use Illuminate\Support\Facades\Route;

// Authenticated routes for company admin
Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:company_admin')->prefix('company-admin')->name('companyAdmin.')->group(function () {
        Route::post('send-invite', [RegistrationController::class, 'sendInvite'])->name('sendInvite');

        // Admin routes for managing projects
        Route::prefix('projects')->name('projects.')->group(function () {
            Route::get('/', [ProjectController::class, 'index'])->name('index');
            Route::get('{projectId}', [ProjectController::class, 'show'])->name('show');
            Route::post('/', [ProjectController::class,'store'])->name('store');
            Route::put('{projectId}', [ProjectController::class, 'update'])->name('update');
            Route::delete('{projectId}', [ProjectController::class, 'destroy'])->name('destroy');
        });
    });
});
