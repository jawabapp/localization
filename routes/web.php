<?php

use Jawabapp\Localization\Http\Controllers\TranslationController;

Route::name('jawab.')->group(function () {
    Route::get('/translation/generate', [TranslationController::class, 'generate'])->name('translation.generate');
    Route::post('/translation/generate', [TranslationController::class, 'generate'])->name('translation.generate');

    Route::resource('translation', TranslationController::class)->only([
        'index', 'edit', 'update', 'destroy'
    ]);
});

