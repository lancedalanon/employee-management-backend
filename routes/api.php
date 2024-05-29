<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:jwt');

Route::post('/login', [AuthController::class, 'login']);

Route::post('/logout', function (Request $request) {
    try {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'Successfully logged out']);
    } catch (JWTException $e) {
        return response()->json(['error' => 'Could not log out'], 500);
    }
});