<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return response()->json([
        'app' => 'Hospital Ticketing System API',
        'version' => '1.0.0',
        'docs' => '/api',
    ]);
});
