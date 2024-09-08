<?php

use App\Http\Controllers\v1\AuthenticationController;
use App\Http\Controllers\v1\ProjectController;
use App\Http\Controllers\v1\DtrController;
use App\Http\Controllers\v1\LeaveRequestController;
use App\Http\Controllers\v1\ProjectTaskController;
use App\Http\Controllers\v1\RegistrationController;
use App\Http\Controllers\v1\UserController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('login', [AuthenticationController::class, 'login'])->name('login');
//Route::post('password/email', [AuthenticationController::class, 'sendResetLinkEmail'])->name('password.email');
//Route::post('password/reset', [AuthenticationController::class, 'reset'])->name('password.reset');
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

    Route::prefix('leave-requests')->name('leaveRequests.')->group(function () {
        Route::get('/', [LeaveRequestController::class, 'index'])->name('index');
        Route::get('{leaveRequestId}', [LeaveRequestController::class, 'show'])->name('show');
        Route::post('/', [LeaveRequestController::class, 'store'])->name('store');
        Route::delete('{leaveRequestId}', [LeaveRequestController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('{projectId}', [ProjectController::class, 'show'])->name('show');

        Route::prefix('{projectId}/tasks')->name('tasks.')->group(function () {
            Route::get('/', [ProjectTaskController::class, 'index'])->name('index');
            Route::get('{taskId}', [ProjectTaskController::class, 'show'])->name('show');
            Route::post('/', [ProjectTaskController::class,'store'])->name('store');
            Route::put('{taskId}', [ProjectTaskController::class, 'update'])->name('update');
            Route::delete('{taskId}', [ProjectTaskController::class, 'destroy'])->name('destroy');

            Route::prefix('{taskId}/users')->name('users.')->group(function () {
                Route::post('add', [ProjectTaskController::class, 'assignUser'])->name('assignUser');
                Route::post('remove', [ProjectTaskController::class, 'removeUser'])->name('removeUser');
            });
        });
    });

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'show'])->name('show');
        Route::put('/personal-information', [UserController::class, 'updatePersonalInformation'])->name('updatePersonalInformation');
        Route::put('/contact-information', [UserController::class, 'updateContactInformation'])->name('updateContactInformation');
        Route::put('/password', [UserController::class, 'updatePassword'])->name('updatePassword');
        Route::put('/api-key', [UserController::class, 'updateApiKey'])->name('updateApiKey');
    });
});
