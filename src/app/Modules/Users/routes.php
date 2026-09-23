<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::view('profile', 'profile')->middleware('auth')->name('profile');
