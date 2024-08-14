<?php

use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\ProjectCompletionController;
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

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
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
            Route::get('/', [AdminUserController::class, 'index'])->name('index');
            Route::get('{userId}', [AdminUserController::class, 'show'])->name('show');
            Route::post('/', [AdminUserController::class, 'store'])->name('store');
            Route::put('{userId}', [AdminUserController::class, 'update'])->name('update');
            Route::delete('{userId}', [AdminUserController::class, 'destroy'])->name('destroy');
        });

        // Admin routes for managing attendances
        Route::prefix('attendances')->name('attendances.')->group(function () {
            Route::get('/', [AdminAttendanceController::class, 'index'])->name('index');
            Route::get('{userId}', [AdminAttendanceController::class, 'show'])->name('show');
        });

        Route::prefix('project-completion')->name('project-completion')->group(function () {
            Route::prefix('employees')->name('employees.')->group(function () {
                // Full-Time Employees
                Route::prefix('full-time')->name('full-time.')->group(function () {
                    Route::get('/', [ProjectCompletionController::class, 'indexEmployeeFullTime'])->name('index');
                    Route::get('{userId}', [ProjectCompletionController::class, 'showEmployeeFullTime'])->name('show');
                });
            
                // Part-Time Employees
                Route::prefix('part-time')->name('part-time.')->group(function () {
                    Route::get('/', [ProjectCompletionController::class, 'indexEmployeePartTime'])->name('index');
                    Route::get('{userId}', [ProjectCompletionController::class, 'showEmployeePartTime'])->name('show');
                });
            });

            Route::prefix('interns')->name('interns.')->group(function () {
                // Full-Time Interns
                Route::prefix('full-time')->name('full-time.')->group(function () {
                    Route::get('/', [ProjectCompletionController::class, 'indexInternFullTime'])->name('index');
                    Route::get('{userId}', [ProjectCompletionController::class, 'showInternFullTime'])->name('show');
                });
            
                // Part-Time Interns
                Route::prefix('part-time')->name('part-time.')->group(function () {
                    Route::get('/', [ProjectCompletionController::class, 'indexInternPartTime'])->name('index');
                    Route::get('{userId}', [ProjectCompletionController::class, 'showInternPartTime'])->name('show');
                });
            });
        });
    });
});
