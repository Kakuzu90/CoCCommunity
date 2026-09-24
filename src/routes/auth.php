<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

// Guest-only surfaces. Rate limiters are the named limiters from AuthServiceProvider (specs/04 §4).
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])->middleware('throttle:register');
    Route::view('register/check-email', 'auth.check-email')->name('register.pending');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:password-reset')->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

// The verify link is a signed URL, honoured signed-in or not (the signature is the proof).
Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

// Authenticated surfaces.
Route::middleware('auth')->group(function () {
    Route::get('email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:verify-email-resend')->name('verification.send');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
