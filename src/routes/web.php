<?php

use App\Http\Controllers\Web\AccountImageController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\PublicProfileController;
use App\Livewire\Accounts\AccountDetail;
use App\Livewire\Accounts\ManageAccounts;
use App\Livewire\Accounts\ManageDisputes;
use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')->name('home');
Route::get('/u/{username}', PublicProfileController::class)->name('profile.show');

Route::middleware(['auth', 'verified'])->group(function (): void {
    // Attach + verify in-game accounts (specs/13). Viewing is read-only for any verified user; the
    // attach/detach writes re-check the manage-own-coc-accounts gate inside the component.
    Route::get('/accounts', ManageAccounts::class)->name('accounts.index');
    // Ownership disputes: file from the conflict on /accounts; respond, add info or withdraw here.
    // Every write re-checks the acting user is the claimant or holder in the service (specs/13 §5).
    Route::get('/accounts/disputes', ManageDisputes::class)->name('accounts.disputes.index');
    Route::post('/accounts/{ulid}/images', [AccountImageController::class, 'store'])->whereUlid('ulid')->name('accounts.images.store');
    Route::delete('/accounts/{ulid}/images/{mediaUlid}', [AccountImageController::class, 'destroy'])
        ->whereUlid('ulid')->whereUlid('mediaUlid')->name('accounts.images.destroy');
});

Route::get('/accounts/{ulid}', AccountDetail::class)->whereUlid('ulid')->name('accounts.show');

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
