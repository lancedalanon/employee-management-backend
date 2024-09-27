<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response('Employee Management System API', 200)
        ->header('Content-Type', 'text/plain');
});
