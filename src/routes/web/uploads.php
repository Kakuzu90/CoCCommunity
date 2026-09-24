<?php

use App\Http\Controllers\Upload\CompleteController;
use App\Http\Controllers\Upload\IntentController;
use Illuminate\Support\Facades\Route;

// Direct-to-storage upload handshake (specs/10 §3). The app never proxies file bytes.
Route::middleware('auth')->group(function () {
    Route::post('/uploads/intent', [IntentController::class, 'store'])
        ->middleware('throttle:upload-intent')
        ->name('uploads.intent');

    Route::post('/uploads/{ulid}/complete', [CompleteController::class, 'store'])
        ->name('uploads.complete');
});
