<?php

use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\PublicProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');
Route::get('/u/{username}', PublicProfileController::class)->name('profile.show');

Route::middleware(['auth', 'can:manage-own-notifications'])->group(function (): void {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->whereUuid('id')->name('notifications.read');
});

/*
 * Section landing routes exist now so navigation resolves everywhere; each renders the
 * app shell around a placeholder until its phase builds the real surface.
 */
Route::view('/bases', 'pages.placeholder', [
    'heading' => 'Base sharing is on the way',
    'body' => 'Publishing, the feed and trending layouts arrive in Phase 3.',
])->name('bases.index');

Route::view('/recruit', 'pages.placeholder', [
    'heading' => 'Recruitment is coming',
    'body' => 'Looking-for-clan and clan recruitment posts arrive after the MVP ships.',
])->name('recruit.index');

Route::view('/search', 'pages.placeholder', [
    'heading' => 'Search is being built',
    'body' => 'Full base, player and clan search arrives in Phase 3.',
])->name('search');

Route::get('/dev/components', function () {
    abort_if(app()->isProduction(), 404);

    return view('dev.components');
})->name('dev.components');

require __DIR__.'/web/uploads.php';

require __DIR__.'/auth.php';

require __DIR__.'/web/settings.php';

require __DIR__.'/web/admin.php';
