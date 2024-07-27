<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DtrController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectTaskController;
use App\Http\Controllers\ProjectTaskStatusController;
use App\Http\Controllers\ProjectUserController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);
Route::post('password/email', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
Route::post('password/reset', [AuthController::class, 'reset'])->name('password.reset');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'showAuthenticatedUser'])->name('show');
        Route::put('/', [UserController::class, 'updatePersonalInformation'])->name('update');
        Route::put('/change-password', [UserController::class, 'changePassword'])->name('changePassword');
    });

    Route::prefix('dtrs')->name('dtrs.')->group(function () {
        Route::get('/', [DtrController::class, 'index'])->name('index');
        Route::get('/{dtrId}', [DtrController::class, 'show'])->name('show');
        Route::post('/time-in', [DtrController::class, 'storeTimeIn'])->name('storeTimeIn');
        Route::post('{dtrId}/break', [DtrController::class, 'storeBreak'])->name('storeBreak');
        Route::post('{dtrId}/resume', [DtrController::class, 'storeResume'])->name('storeResume');
        Route::post('{dtrId}/time-out', [DtrController::class, 'storeTimeOut'])->name('storeTimeOut');
    });

    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::get('{projectId}', [ProjectController::class, 'show'])->name('show');

        Route::prefix('{projectId}/users')->name('users.')->group(function () {
            Route::get('/', [ProjectUserController::class, 'index'])->name('index');
        });

        Route::prefix('{projectId}/tasks')->name('tasks.')->group(function () {
            Route::get('/', [ProjectTaskController::class, 'getTasks'])->name('getTasks');
            Route::get('{taskId}', [ProjectTaskController::class, 'getTaskById'])->name('getTaskById');
            Route::post('/', [ProjectTaskController::class, 'createTask'])->name('createTask');
            Route::put('{taskId}', [ProjectTaskController::class, 'updateTask'])->name('updateTask');
            Route::delete('{taskId}', [ProjectTaskController::class, 'deleteTask'])->name('deleteTask');

            Route::prefix('{taskId}/statuses')->name('statuses.')->group(function () {
                Route::get('/', [ProjectTaskStatusController::class, 'getStatuses'])->name('getStatuses');
                Route::get('{statusId}', [ProjectTaskStatusController::class, 'getStatusById'])->name('getStatusById');
                Route::post('/', [ProjectTaskStatusController::class, 'createStatus'])->name('createStatus');
                Route::put('{statusId}', [ProjectTaskStatusController::class, 'updateStatus'])->name('updateStatus');
                Route::delete('{statusId}', [ProjectTaskStatusController::class, 'deleteStatus'])->name('deleteStatus');
            });
        });
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::prefix('projects')->name('projects.')->group(function () {
            Route::post('/', [ProjectController::class, 'store'])->name('store');
            Route::put('{projectId}', [ProjectController::class, 'update'])->name('update');
            Route::delete('{projectId}', [ProjectController::class, 'destroy'])->name('destroy');

            Route::prefix('{projectId}/users')->name('users.')->group(function () {
                Route::post('/add', [ProjectUserController::class, 'storeUser'])->name('storeUser');
                Route::post('/remove', [ProjectUserController::class, 'destroyUser'])->name('destroyUser');
                Route::put('/role', [ProjectUserController::class, 'updateUser'])->name('updateUser');
            });
        });
    });
});
