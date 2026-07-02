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

    // Artist store
    Route::livewire('artist/store', 'pages::artist.store')
        ->middleware(['role:artist'])
        ->name('artist.store');

    Route::livewire('artist/orders', 'pages::artist.orders')
        ->middleware(['role:artist'])
        ->name('artist.orders');

    // Artist VIP plans
    Route::livewire('artist/vip', 'pages::artist.vip')
        ->middleware(['role:artist'])
        ->name('artist.vip');

    // Artist dedications
    Route::livewire('artist/dedications', 'pages::artist.dedications')
        ->middleware(['role:artist'])
        ->name('artist.dedications');

    // Artist content rights (copyright)
    Route::livewire('artist/content-rights', 'pages::artist.content-rights')
        ->middleware(['role:artist'])
        ->name('artist.content-rights');
});

// Fan dedication request (auth required for now)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dedications/request', 'pages::request-dedication')
        ->name('dedications.request');
});

// Voting contests
Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('contests', 'pages::contests')->name('contests');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('admin/contests', 'pages::artist.contests')
        ->middleware(['role:admin'])
        ->name('admin.contests');
});

require __DIR__.'/settings.php';
