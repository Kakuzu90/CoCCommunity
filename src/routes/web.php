<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dev/components', function () {
    abort_if(app()->isProduction(), 404);

    return view('dev.components');
})->name('dev.components');
