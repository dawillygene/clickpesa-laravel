<?php

use Illuminate\Support\Facades\Route;
use Dawilly\Dawilly\Http\Controllers\ClickpesaController;

Route::prefix('clickpesa')->group(function () {
    Route::post('callback', [ClickpesaController::class, 'callback'])->name('clickpesa.callback');
});
