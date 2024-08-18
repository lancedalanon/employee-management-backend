
<?php

use App\Http\Controllers\Admin\CompanyController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::prefix('company')->name('company.')->group(function () {
            Route::get('/', [CompanyController::class, 'index'])->name('index');
            Route::get('{companyId}', [CompanyController::class, 'show'])->name('show');
            Route::post('{userId}', [CompanyController::class, 'store'])->name('store');
            Route::put('{companyId}', [CompanyController::class, 'update'])->name('update');
            Route::delete('{companyId}', [CompanyController::class, 'destroy'])->name('destroy');
        });
    });
});