<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DtrController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectTaskController;
use App\Http\Controllers\ProjectTaskSubtaskController;
use App\Http\Controllers\ProjectUserController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WeeklyReportController;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::post('login', [AuthController::class, 'login']);
Route::post('password/email', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
Route::post('password/reset', [AuthController::class, 'reset'])->name('password.reset');
Route::post('register', [UserController::class, 'register'])->name('register');
Route::post('register/company-admin', [UserController::class, 'registerCompanyAdmin'])->name('register.company-admin');

Route::middleware('auth:sanctum')->group(function () {
    // Routes for authenticated users
    Route::post('logout', [AuthController::class, 'logout']);

    // User-related routes
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'show'])->name('show');
        Route::put('/', [UserController::class, 'updatePersonalInformation'])->name('updatePersonalInformation');
        Route::put('change-password', [UserController::class, 'updatePassword'])->name('updatePassword');
    });

    // Daily Time Record (DTR) routes
    Route::prefix('dtrs')->name('dtrs.')->group(function () {
        Route::get('/', [DtrController::class, 'index'])->name('index');
        Route::get('{dtrId}', [DtrController::class, 'show'])->name('show');
        Route::post('time-in', [DtrController::class, 'storeTimeIn'])->name('storeTimeIn');
        Route::post('{dtrId}/break', [DtrController::class, 'storeBreak'])->name('storeBreak');
        Route::post('{dtrId}/resume', [DtrController::class, 'storeResume'])->name('storeResume');
        Route::post('{dtrId}/time-out', [DtrController::class, 'storeTimeOut'])->name('storeTimeOut');
    });

    Route::prefix('leave-requests')->name('leaveRequests.')->group(function () {
        Route::get('/', [LeaveRequestController::class, 'index'])->name('index');
        Route::get('{leaveRequestId}', [LeaveRequestController::class, 'show'])->name('show');
        Route::post('/', [LeaveRequestController::class, 'bulkStore'])->name('bulkStore');
    });

    // Project-related routes
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('{projectId}', [ProjectController::class, 'show'])->name('show');

        // Routes for managing users within a project
        Route::prefix('{projectId}/users')->name('users.')->group(function () {
            Route::get('/', [ProjectUserController::class, 'indexUser'])->name('indexUser');
            Route::get('{userId}', [ProjectUserController::class, 'showUser'])->name('showUser');
        });

        // Routes for managing tasks within a project
        Route::prefix('{projectId}/tasks')->name('tasks.')->group(function () {
            Route::get('/', [ProjectTaskController::class, 'index'])->name('index');
            Route::get('{taskId}', [ProjectTaskController::class, 'show'])->name('show');
            Route::post('/', [ProjectTaskController::class, 'store'])->name('store');
            Route::put('{taskId}', [ProjectTaskController::class, 'update'])->name('update');
            Route::delete('{taskId}', [ProjectTaskController::class, 'destroy'])->name('destroy');

            // Routes for managing sub tasks
            Route::prefix('{taskId}/subtasks')->name('subtasks.')->group(function () {
                Route::get('/', [ProjectTaskSubtaskController::class, 'index'])->name('index');
                Route::get('{subtaskId}', [ProjectTaskSubtaskController::class, 'show'])->name('show');
                Route::post('/', [ProjectTaskSubtaskController::class, 'store'])->name('store');
                Route::put('{subtaskId}', [ProjectTaskSubtaskController::class, 'update'])->name('update');
                Route::delete('{subtaskId}', [ProjectTaskSubtaskController::class, 'destroy'])->name('destroy');

                Route::prefix('{subtaskId}/users')->name('users.')->group(function () {
                    Route::post('{userId}/add', [ProjectTaskSubtaskController::class, 'addUser'])->name('addUser');
                    Route::post('{userId}/remove', [ProjectTaskSubtaskController::class, 'removeUser'])->name('removeUser');
                });
            });

            Route::prefix('{taskId}/users')->name('users.')->group(function () {
                Route::post('{userId}/add', [ProjectTaskController::class, 'addUser'])->name('addUser');
                Route::post('{userId}/remove', [ProjectTaskController::class, 'removeUser'])->name('removeUser');
            });
        });
    });

    // Post-related routes
    Route::prefix('posts')->name('posts.')->group(function () {
        Route::get('/', [PostController::class, 'index'])->name('index');
        Route::get('{postId}', [PostController::class, 'show'])->name('show');
    });

    // Weekly report-related routes
    Route::prefix('weekly-reports')->name('weeklyReports.')->group(function () {
        Route::get('options', [WeeklyReportController::class, 'showOptions'])->name('showOptions');
        Route::get('images', [WeeklyReportController::class, 'showEndOfTheDayReportImages'])->name('showEndOfTheDayReportImages');
    });
});
