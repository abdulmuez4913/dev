<?php

use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Controllers\StaffController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:staff')->group(function () {
    Route::get('/staff-me', [StaffAuthController::class, 'me']);
    Route::post('/staff-logout', [StaffAuthController::class, 'logout']);
    Route::get('/staff', [StaffController::class, 'index']);
    Route::post('/staff', [StaffController::class, 'store']);
    Route::get('/staff/{id}', [StaffController::class, 'show']);
    Route::put('/staff/{id}', [StaffController::class, 'update']);
    Route::delete('/staff/{id}', [StaffController::class, 'destroy']);
});

Route::middleware('auth:customer')->group(function () {
    Route::get('/customer-me', [CustomerAuthController::class, 'me']);
    Route::post('/customer-logout', [CustomerAuthController::class, 'logout']);
});

Route::post('/staff/login', [StaffAuthController::class, 'login']);
Route::post('/customer/login', [CustomerAuthController::class, 'login']);
Route::post('/customer', [CustomerController::class, 'store']);
