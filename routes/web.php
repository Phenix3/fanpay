<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Language switcher
Route::get('/locale/{locale}', function (string $locale) {
    if (! in_array($locale, ['fr', 'en'])) {
        $locale = 'fr';
    }

    app()->setLocale($locale);

    if (auth()->check()) {
        auth()->user()->update(['locale' => $locale]);
    }

    return redirect()->back()->cookie('locale', $locale, 60 * 24 * 365);
})->name('locale.switch');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
