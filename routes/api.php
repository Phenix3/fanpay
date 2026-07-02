<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| RESTful API pour l'ensemble des modules FanPay.
| Authentification via Sanctum (token-based).
|
*/

// === Public routes (no auth) ===

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('api.health');

// Payment webhooks (no auth - verified by gateway signature)
Route::post('/webhooks/payment', [\App\Http\Controllers\Api\WebhookController::class, 'handle'])
    ->name('webhooks.payment');

// Auth - Get Sanctum token
Route::post('/auth/token', [\App\Http\Controllers\Api\AuthController::class, 'token'])
    ->name('api.auth.token');

// Catalog - Public read-only
Route::name('api.')->group(function () {
    // Genres
    Route::get('/genres', [\App\Http\Controllers\Api\Catalog\GenreController::class, 'index'])->name('genres.index');
    Route::get('/genres/{genre}', [\App\Http\Controllers\Api\Catalog\GenreController::class, 'show'])->name('genres.show');

    // Albums
    Route::get('/albums', [\App\Http\Controllers\Api\Catalog\AlbumController::class, 'index'])->name('albums.index');
    Route::get('/albums/{album}', [\App\Http\Controllers\Api\Catalog\AlbumController::class, 'show'])->name('albums.show');

    // Tracks
    Route::get('/tracks', [\App\Http\Controllers\Api\Catalog\TrackController::class, 'index'])->name('tracks.index');
    Route::get('/tracks/{track}', [\App\Http\Controllers\Api\Catalog\TrackController::class, 'show'])->name('tracks.show');

    // Artists (public profiles - wallet never exposed)
    Route::get('/artists', [\App\Http\Controllers\Api\Users\ArtistController::class, 'index'])->name('artists.index');
    Route::get('/artists/{user}', [\App\Http\Controllers\Api\Users\ArtistController::class, 'show'])->name('artists.show');

    // Store - Products
    Route::get('/products', [\App\Http\Controllers\Api\Store\ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [\App\Http\Controllers\Api\Store\ProductController::class, 'show'])->name('products.show');

    // VIP Plans
    Route::get('/vip-plans', [\App\Http\Controllers\Api\Vip\VipPlanController::class, 'index'])->name('vip-plans.index');
    Route::get('/vip-plans/{vipPlan}', [\App\Http\Controllers\Api\Vip\VipPlanController::class, 'show'])->name('vip-plans.show');

    // Events (upcoming only)
    Route::get('/events', [\App\Http\Controllers\Api\Events\EventController::class, 'index'])->name('events.index');
    Route::get('/events/{event}', [\App\Http\Controllers\Api\Events\EventController::class, 'show'])->name('events.show');

    // Voting - Contests (active only)
    Route::get('/contests', [\App\Http\Controllers\Api\Voting\ContestController::class, 'index'])->name('contests.index');
    Route::get('/contests/{contest}', [\App\Http\Controllers\Api\Voting\ContestController::class, 'show'])->name('contests.show');

    // Dedications - Available artists
    Route::get('/dedications/artists', [\App\Http\Controllers\Api\Dedications\DedicationController::class, 'artists'])->name('dedications.artists');
});

// === Authenticated routes (Sanctum) ===
Route::middleware('auth:sanctum')->name('api.')->group(function () {

    // Current user profile
    Route::get('/user', [\App\Http\Controllers\Api\AuthController::class, 'user'])->name('user');

    // Dedications - Create a request
    Route::post('/dedications', [\App\Http\Controllers\Api\Dedications\DedicationController::class, 'store'])->name('dedications.store');

    // Voting - Cast a vote
    Route::post('/contests/{contest}/vote', [\App\Http\Controllers\Api\Voting\ContestController::class, 'vote'])->name('contests.vote');

    // --- Artist-only routes (require role:artist) ---
    Route::middleware(['role:artist'])->group(function () {

        // Orders
        Route::get('/artist/orders', [\App\Http\Controllers\Api\Store\OrderController::class, 'index'])->name('artist.orders.index');
        Route::get('/artist/orders/{order}', [\App\Http\Controllers\Api\Store\OrderController::class, 'show'])->name('artist.orders.show');

        // Events - Full CRUD
        Route::post('/artist/events', [\App\Http\Controllers\Api\Events\EventController::class, 'store'])->name('artist.events.store');
        Route::put('/artist/events/{event}', [\App\Http\Controllers\Api\Events\EventController::class, 'update'])->name('artist.events.update');
        Route::delete('/artist/events/{event}', [\App\Http\Controllers\Api\Events\EventController::class, 'destroy'])->name('artist.events.destroy');

        // VIP Plans - Full CRUD
        Route::post('/artist/vip-plans', [\App\Http\Controllers\Api\Vip\VipPlanController::class, 'store'])->name('artist.vip-plans.store');
        Route::put('/artist/vip-plans/{vipPlan}', [\App\Http\Controllers\Api\Vip\VipPlanController::class, 'update'])->name('artist.vip-plans.update');
        Route::delete('/artist/vip-plans/{vipPlan}', [\App\Http\Controllers\Api\Vip\VipPlanController::class, 'destroy'])->name('artist.vip-plans.destroy');
    });

    // --- Admin-only routes (require role:admin) ---
    Route::middleware(['role:admin'])->group(function () {
        // Contests - Admin CRUD
        Route::post('/admin/contests', [\App\Http\Controllers\Api\Voting\ContestController::class, 'store'])->name('admin.contests.store');
        Route::put('/admin/contests/{contest}', [\App\Http\Controllers\Api\Voting\ContestController::class, 'update'])->name('admin.contests.update');
        Route::delete('/admin/contests/{contest}', [\App\Http\Controllers\Api\Voting\ContestController::class, 'destroy'])->name('admin.contests.destroy');
    });
});
