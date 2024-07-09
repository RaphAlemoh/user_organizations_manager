<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


Route::prefix('/auth')->group(function () {
    Route::controller(AuthController::class)
        ->group(function () {
            Route::post('register', 'register');
            Route::post('login', 'login');
        });
});

Route::middleware('auth:api')->group(function () {
    Route::apiResource('users', UserController::class);
    Route::get('organisations', [OrganizationController::class, 'index']);
    Route::get('organisations/{orgId}', [OrganizationController::class, 'show']);
    Route::post('organisations', [OrganizationController::class, 'store']);
    Route::post('organisations/{orgId}/users', [OrganizationController::class, 'add_user_org']);
});
