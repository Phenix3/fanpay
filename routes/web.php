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

// OTP routes (guest)
Route::middleware('guest')->group(function () {
    Route::get('/verify-otp/{purpose?}', [\App\Http\Controllers\Auth\OtpController::class, 'show'])
        ->name('otp.verify');
    Route::post('/otp/send', [\App\Http\Controllers\Auth\OtpController::class, 'send'])
        ->name('otp.send');
    Route::post('/otp/verify', [\App\Http\Controllers\Auth\OtpController::class, 'verify'])
        ->name('otp.verify.submit');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    // Artist wallet
    Route::livewire('artist/wallet', 'pages::artist.wallet')
        ->middleware(['role:artist'])
        ->name('artist.wallet');

    // Artist catalog
    Route::livewire('artist/catalog', 'pages::artist.catalog')
        ->middleware(['role:artist'])
        ->name('artist.catalog');

    Route::livewire('artist/catalog/albums/{album}/tracks', 'pages::artist.album-tracks')
        ->middleware(['role:artist'])
        ->name('artist.catalog.albums.tracks');
});

require __DIR__.'/settings.php';
