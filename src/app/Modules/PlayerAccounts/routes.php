<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Volt::route('accounts', 'pages.accounts.index')->name('accounts.index');
});
