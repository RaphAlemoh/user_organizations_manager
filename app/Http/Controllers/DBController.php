<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;

class DBController extends Controller
{
    public function  dbMigration()
    {

        Artisan::call('migrate:fresh', [
            '--force' => true
        ]);

        Artisan::call('db:seed');

        return Artisan::output();
    }
}
