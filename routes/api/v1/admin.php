<?php

use App\Http\Controllers\v1\Admin\CompanyController;
use App\Http\Controllers\v1\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Authenticated routes for admin
Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::prefix('companies')->name('companies.')->group(function () {
            Route::get('/', [CompanyController::class, 'index'])->name('index');
            Route::get('{companyId}', [CompanyController::class,'show'])->name('show');
            Route::post('/', [CompanyController::class,'store'])->name('store');
            Route::put('{companyId}', [CompanyController::class,'update'])->name('update');
            Route::delete('{companyId}', [CompanyController::class,'destroy'])->name('destroy');
        });

        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('{userId}', [UserController::class,'show'])->name('show');
            Route::post('/', [UserController::class,'store'])->name('store');
            Route::put('{userId}', [UserController::class,'update'])->name('update');
            Route::delete('{userId}', [UserController::class,'destroy'])->name('destroy');

            Route::prefix('{userId}')->group(function () {
                Route::post('role', [UserController::class, 'changeRole'])->name('changeRole');
                Route::post('password', [UserController::class, 'changePassword'])->name('changePassword');
            });
        });
    });
});