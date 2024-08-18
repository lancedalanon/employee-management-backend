<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('v1')->name('v1.')->group(function () {
    //require __DIR__ . '/api/v1/admin.php';
    //require __DIR__ . '/api/v1/company_admin.php';
    require __DIR__ . '/api/v1/user.php';
});
