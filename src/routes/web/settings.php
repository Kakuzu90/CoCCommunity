<?php

use App\Http\Controllers\Settings\AvatarController;
use App\Http\Controllers\Settings\DeletionController;
use App\Http\Controllers\Settings\PrivacyController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\SessionController;
use Illuminate\Support\Facades\Route;

/*
 * Account settings. Editing is a write, so it sits behind the verified-email and active-status
 * gates (specs/04 §3). Session and deletion recovery routes stay available to signed-in users.
 */
Route::middleware(['auth', 'verified', 'active'])->prefix('settings')->name('settings.')->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::post('profile/avatar', [AvatarController::class, 'store'])->name('profile.avatar.store');
    Route::delete('profile/avatar', [AvatarController::class, 'destroy'])->name('profile.avatar.destroy');

    Route::get('privacy', [PrivacyController::class, 'edit'])->name('privacy.edit');
    Route::put('privacy', [PrivacyController::class, 'update'])->name('privacy.update');

    Route::get('security', [SecurityController::class, 'edit'])->name('security.edit');
    Route::put('security/password', [SecurityController::class, 'updatePassword'])->name('security.password.update');
    Route::put('security/email', [SecurityController::class, 'updateEmail'])->name('security.email.update');
});

Route::middleware('auth')->prefix('settings')->name('settings.')->group(function () {
    Route::get('sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::delete('sessions/others', [SessionController::class, 'destroyOthers'])->name('sessions.destroy-others');
    Route::delete('sessions', [SessionController::class, 'destroyAll'])->name('sessions.destroy-all');
    Route::delete('sessions/{sessionId}', [SessionController::class, 'destroy'])->name('sessions.destroy');

    Route::get('deletion', [DeletionController::class, 'show'])->name('deletion.show');
    Route::post('deletion', [DeletionController::class, 'request'])->middleware(['verified', 'active'])->name('deletion.request');
    Route::delete('deletion', [DeletionController::class, 'cancel'])->name('deletion.cancel');
});
