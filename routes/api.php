<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DtrController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectTaskController;
use App\Http\Controllers\ProjectTaskStatusController;
use App\Http\Controllers\ProjectUserController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::post('login', [AuthController::class, 'login']);
Route::post('password/email', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
Route::post('password/reset', [AuthController::class, 'reset'])->name('password.reset');

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

    // Project-related routes
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('{projectId}', [ProjectController::class, 'show'])->name('show');

        // Routes for managing users within a project
        Route::prefix('{projectId}/users')->name('users.')->group(function () {
            Route::get('/', [ProjectUserController::class, 'index'])->name('index');
        });

        // Routes for managing tasks within a project
        Route::prefix('{projectId}/tasks')->name('tasks.')->group(function () {
            Route::get('/', [ProjectTaskController::class, 'index'])->name('index');
            Route::get('{taskId}', [ProjectTaskController::class, 'show'])->name('show');
            Route::post('/', [ProjectTaskController::class, 'store'])->name('store');
            Route::put('{taskId}', [ProjectTaskController::class, 'update'])->name('update');
            Route::delete('{taskId}', [ProjectTaskController::class, 'destroy'])->name('destroy');

            // Routes for managing task statuses
            Route::prefix('{taskId}/statuses')->name('statuses.')->group(function () {
                Route::get('/', [ProjectTaskStatusController::class, 'index'])->name('index');
                Route::get('{statusId}', [ProjectTaskStatusController::class, 'show'])->name('show');
                Route::post('/', [ProjectTaskStatusController::class, 'store'])->name('store');
                Route::put('{statusId}', [ProjectTaskStatusController::class, 'update'])->name('update');
                Route::delete('{statusId}', [ProjectTaskStatusController::class, 'destroy'])->name('destroy');
            });
        });
    });

    // Post-related routes
    Route::prefix('posts')->name('posts.')->group(function () {
        Route::get('/', [PostController::class, 'index'])->name('index');
        Route::get('{postId}', [PostController::class, 'show'])->name('show');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        // Admin routes for managing projects
        Route::prefix('projects')->name('projects.')->group(function () {
            Route::post('/', [ProjectController::class, 'store'])->name('store');
            Route::put('{projectId}', [ProjectController::class, 'update'])->name('update');
            Route::delete('{projectId}', [ProjectController::class, 'destroy'])->name('destroy');

            // Admin routes for managing users within a project
            Route::prefix('{projectId}/users')->name('users.')->group(function () {
                Route::post('add', [ProjectUserController::class, 'storeUser'])->name('storeUser');
                Route::post('remove', [ProjectUserController::class, 'destroyUser'])->name('destroyUser');
                Route::put('role', [ProjectUserController::class, 'updateUser'])->name('updateUser');
            });
        });

        // Admin routes for managing posts
        Route::prefix('posts')->name('posts.')->group(function () {
            Route::post('/', [PostController::class, 'store'])->name('store');
            Route::put('{postId}', [PostController::class, 'update'])->name('update');
            Route::delete('{postId}', [PostController::class, 'destroy'])->name('destroy');
        });
    });
});
