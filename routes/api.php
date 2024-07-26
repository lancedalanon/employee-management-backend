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
        Route::post('/break/{dtrId}', [DtrController::class, 'storeBreak'])->name('storeBreak');
        Route::post('/resume/{dtrId}', [DtrController::class, 'storeResume'])->name('storeResume');
        Route::post('/time-out/{dtrId}', [DtrController::class, 'storeTimeOut'])->name('storeTimeOut');
    });

    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/', [ProjectController::class, 'getProjects'])->name('getProjects');
        Route::get('/{projectId}', [ProjectController::class, 'getProjectsById'])->name('getProjectsById');
        Route::get('/{projectId}/users', [ProjectUserController::class, 'getProjectUsers'])->name('getProjectUsers');

        Route::prefix('{projectId}/tasks')->name('tasks.')->group(function () {
            Route::get('/', [ProjectTaskController::class, 'getTasks'])->name('getTasks');
            Route::get('/{projectId}', [ProjectTaskController::class, 'getTaskById'])->name('getTaskById');
            Route::post('/', [ProjectTaskController::class, 'createTask'])->name('createTask');
            Route::put('/{projectId}', [ProjectTaskController::class, 'updateTask'])->name('updateTask');
            Route::delete('/{projectId}', [ProjectTaskController::class, 'deleteTask'])->name('deleteTask');

            Route::prefix('{taskId}/statuses')->name('statuses.')->group(function () {
                Route::get('/', [ProjectTaskStatusController::class, 'getStatuses'])->name('getStatuses');
                Route::get('/{taskId}', [ProjectTaskStatusController::class, 'getStatusById'])->name('getStatusById');
                Route::post('/', [ProjectTaskStatusController::class, 'createStatus'])->name('createStatus');
                Route::put('/{taskId}', [ProjectTaskStatusController::class, 'updateStatus'])->name('updateStatus');
                Route::delete('/{taskId}', [ProjectTaskStatusController::class, 'deleteStatus'])->name('deleteStatus');
            });
        });
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::prefix('projects')->name('projects.')->group(function () {
            Route::post('/', [ProjectController::class, 'createProject'])->name('createProject');
            Route::put('/{projectId}', [ProjectController::class, 'updateProject'])->name('updateProject');
            Route::delete('/{projectId}', [ProjectController::class, 'deleteProject'])->name('deleteProject');
            Route::post('/{projectId}/add-users', [ProjectUserController::class, 'addUsersToProject'])->name('addUsersToProject');
            Route::post('/{projectId}/remove-users', [ProjectUserController::class, 'removeUsersFromProject'])->name('removeUsersFromProject');
            Route::put('/{projectId}/update-role', [ProjectUserController::class, 'updateProjectRole'])->name('updateProjectRole');
        });
    });
});
