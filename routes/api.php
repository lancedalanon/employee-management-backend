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
        Route::patch('/change-password', [UserController::class, 'changePassword'])->name('changePassword');
    });

    Route::prefix('dtr')->name('dtr.')->group(function () {
        Route::get('/', [DtrController::class, 'getDtr'])->name('getDtr');
        Route::get('/{dtr}', [DtrController::class, 'getDtrById'])->name('getDtrById');
        Route::post('/time-in', [DtrController::class, 'timeIn'])->name('timeIn');
        Route::post('/break/{dtr}', [DtrController::class, 'break'])->name('break');
        Route::post('/resume/{dtr}', [DtrController::class, 'resume'])->name('resume');
        Route::post('/time-out/{dtr}', [DtrController::class, 'timeOut'])->name('timeOut');
    });

    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'getProjects'])->name('getProjects');
        Route::get('/{id}', [ProjectController::class, 'getProjectsById'])->name('getProjectsById');
        Route::get('/{projectId}/users', [ProjectUserController::class, 'getProjectUsers'])->name('getProjectUsers');

        Route::prefix('{projectId}/tasks')->name('tasks.')->group(function () {
            Route::get('/', [ProjectTaskController::class, 'getTasks'])->name('getTasks');
            Route::get('/{id}', [ProjectTaskController::class, 'getTaskById'])->name('getTaskById');
            Route::post('/create', [ProjectTaskController::class, 'createTask'])->name('createTask');
            Route::put('/{id}', [ProjectTaskController::class, 'updateTask'])->name('updateTask');
            Route::delete('/{id}', [ProjectTaskController::class, 'deleteTask'])->name('deleteTask');

            Route::prefix('{taskId}/statuses')->name('statuses.')->group(function () {
                Route::get('/', [ProjectTaskStatusController::class, 'getStatuses'])->name('getStatuses');
                Route::get('/{id}', [ProjectTaskStatusController::class, 'getStatusById'])->name('getStatusById');
                Route::post('/create', [ProjectTaskStatusController::class, 'createStatus'])->name('createStatus');
                Route::put('/{id}', [ProjectTaskStatusController::class, 'updateStatus'])->name('updateStatus');
                Route::delete('/{id}', [ProjectTaskStatusController::class, 'deleteStatus'])->name('deleteStatus');
            });
        });
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::prefix('projects')->name('projects.')->group(function () {
            Route::post('/create', [ProjectController::class, 'createProject'])->name('createProject');
            Route::put('/{id}', [ProjectController::class, 'updateProject'])->name('updateProject');
            Route::delete('/{id}', [ProjectController::class, 'deleteProject'])->name('deleteProject');
            Route::post('/{projectId}/add-users', [ProjectUserController::class, 'addUsersToProject'])->name('addUsersToProject');
            Route::post('/{projectId}/remove-users', [ProjectUserController::class, 'removeUsersFromProject'])->name('removeUsersFromProject');
        });
    });
});
