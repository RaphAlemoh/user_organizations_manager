<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class DBController extends Controller
{
    public function  dbMigration()
    {

        Artisan::call('migrate:fresh', [
            '--force' => true
        ]);
        return Artisan::output();
    }
}
