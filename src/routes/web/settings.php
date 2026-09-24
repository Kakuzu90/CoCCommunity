<?php

use App\Http\Controllers\Settings\AvatarController;
use App\Http\Controllers\Settings\PrivacyController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;

/*
 * Account settings. Editing is a write, so it sits behind the verified-email and active-status
 * gates (specs/04 §3). Sessions/deletion and privacy join this group in their own tasks.
 */
Route::middleware(['auth', 'verified', 'active'])->prefix('settings')->name('settings.')->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::post('profile/avatar', [AvatarController::class, 'store'])->name('profile.avatar.store');
    Route::delete('profile/avatar', [AvatarController::class, 'destroy'])->name('profile.avatar.destroy');

    Route::get('privacy', [PrivacyController::class, 'edit'])->name('privacy.edit');
    Route::put('privacy', [PrivacyController::class, 'update'])->name('privacy.update');
});
