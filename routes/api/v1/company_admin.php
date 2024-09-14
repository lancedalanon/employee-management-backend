<?php

use App\Http\Controllers\v1\CompanyAdmin\ProjectController;
use App\Http\Controllers\v1\CompanyAdmin\ProjectUserController;
use App\Http\Controllers\v1\RegistrationController;
use App\Http\Controllers\v1\CompanyAdmin\AttendanceController;
use App\Http\Controllers\v1\CompanyAdmin\ProjectCompletionController;
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

            // Admin routes for managing users within a project
            Route::prefix('{projectId}/users')->name('users.')->group(function () {
                Route::get('/', [ProjectUserController::class, 'index'])->name('index');
                Route::get('{userId}', [ProjectUserController::class, 'show'])->name('show');
                Route::post('/add', [ProjectUserController::class, 'bulkAddUsers'])->name('bulkAddUsers');
                Route::post('/remove', [ProjectUserController::class, 'bulkRemoveUsers'])->name('bulkRemoveUsers');
                Route::put('{userId}/change-role', [ProjectUserController::class, 'changeRole'])->name('changeRole');
            });
        });

        // Admin routes for managing attendances
        Route::prefix('attendances')->name('attendances.')->group(function () {
            Route::get('/', [AttendanceController::class, 'index'])->name('index');
            Route::get('{userId}', [AttendanceController::class, 'show'])->name('show');
        });

        // Admin routes for managing project completions
        Route::prefix('project-completions')->name('projectCompletions.')->group(function () {
            Route::get('/', [ProjectCompletionController::class, 'index'])->name('index');
            Route::get('{userId}', [ProjectCompletionController::class, 'show'])->name('show');
        });
    });
});
