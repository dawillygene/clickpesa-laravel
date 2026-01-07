<?php

use Illuminate\Support\Facades\Route;
use Dawilly\Dawilly\Http\Controllers\ClickpesaController;
use Dawilly\Dawilly\Middleware\VerifyClickpesaSignature;

Route::prefix('clickpesa')->group(function () {
    Route::post('callback', [ClickpesaController::class, 'callback'])
        ->name('clickpesa.callback')
        ->middleware(VerifyClickpesaSignature::class)
        ->withoutMiddleware(['web', 'csrf']); // Webhooks should not have CSRF protection
});
