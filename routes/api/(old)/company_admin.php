
<?php

use App\Http\Controllers\CompanyAdmin\AttendanceController as CompanyAdminAttendanceController;
use App\Http\Controllers\CompanyAdmin\CompanyController as CompanyAdminCompanyController;
use App\Http\Controllers\CompanyAdmin\ProjectCompletionController as CompanyProjectCompletionController;
use App\Http\Controllers\CompanyAdmin\UserController as CompanyAdminUserController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectUserController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:company-admin')->prefix('company-admin')->name('companyAdmin.')->group(function () {

        // Admin routes for sending invites via email
        Route::post('send-invite', [UserController::class, 'sendInvite'])->name('send-invite');

        // Admin routes for managing company profile
        Route::prefix('company')->name('company.')->group(function () {
            Route::get('{companyId}', [CompanyAdminCompanyController::class, 'show'])->name('show');
            Route::post('/', [CompanyAdminCompanyController::class, 'store'])->name('store');
            Route::put('{companyId}', [CompanyAdminCompanyController::class, 'update'])->name('update');
            Route::delete('{companyId}', [CompanyAdminCompanyController::class, 'deactivate'])->name('deactivate');
        });

        // Admin routes for managing user's leave requests
        Route::prefix('leave-requests')->name('leaveRequests.')->group(function () {
            Route::get('/', [LeaveRequestController::class, 'indexAdmin'])->name('indexAdmin');
            Route::get('{leaveRequestId}', [LeaveRequestController::class, 'showAdmin'])->name('showAdmin');
            Route::put('{leaveRequestId}', [LeaveRequestController::class, 'update'])->name('update');
            Route::patch('/', [LeaveRequestController::class, 'bulkUpdate'])->name('bulkUpdate');
            Route::delete('{leaveRequestId}', [LeaveRequestController::class, 'destroy'])->name('destroy');
            Route::delete('/', [LeaveRequestController::class, 'bulkDestroy'])->name('bulkDestroy');
        });

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

        // Admin routes for managing users
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [CompanyAdminUserController::class, 'index'])->name('index');
            Route::get('{userId}', [CompanyAdminUserController::class, 'show'])->name('show');
            Route::post('/', [CompanyAdminUserController::class, 'store'])->name('store');
            Route::put('{userId}', [CompanyAdminUserController::class, 'update'])->name('update');
            Route::delete('{userId}', [CompanyAdminUserController::class, 'destroy'])->name('destroy');
        });

        // Admin routes for managing attendances
        Route::prefix('attendances')->name('attendances.')->group(function () {
            Route::get('/', [CompanyAdminAttendanceController::class, 'index'])->name('index');
            Route::get('{userId}', [CompanyAdminAttendanceController::class, 'show'])->name('show');
        });

        Route::prefix('project-completions')->name('projectCompletions.')->group(function () {
            Route::get('/', [CompanyProjectCompletionController::class, 'index'])->name('index');
            Route::get('{userId}', [CompanyProjectCompletionController::class, 'show'])->name('show');
        });
    });
});