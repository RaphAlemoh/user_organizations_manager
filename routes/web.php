<?php

use App\Http\Controllers\DBController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::controller(DBController::class)
    ->group(function () {
        Route::get('migration', 'dbmigration')->name('admin.migration');
    });
