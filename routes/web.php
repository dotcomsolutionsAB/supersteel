<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CreateController;

use App\Http\Controllers\ViewController;

use App\Http\Middleware\GetUserRole;

Route::get('/', function () {
    return view('welcome');
});