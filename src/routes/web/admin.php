<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SanctionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
 * Admin back-office (specs/04 §2, specs/12, specs/18 §6). The whole area sits behind auth, a verified
 * email and a role floor of admin — defence in depth. Each controller additionally re-checks the exact
 * ability through a Gate, and every sanction re-checks authorization on the User policy at the service
 * layer (route middleware is never the sole check: specs/04 §3). Users are addressed by username, not
 * by autoincrement id (specs/04 §3 IDOR).
 */
Route::middleware(['auth', 'verified', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{username}', [UserController::class, 'show'])->name('users.show');

        Route::post('users/{username}/sanctions', [SanctionController::class, 'store'])->name('users.sanctions.store');
        Route::delete('users/{username}/sanctions', [SanctionController::class, 'destroy'])->name('users.sanctions.destroy');

        Route::get('logs', [AuditLogController::class, 'index'])->name('logs.index');
    });
