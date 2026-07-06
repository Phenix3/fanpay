<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Catalog\AlbumController;
use App\Http\Controllers\Api\Catalog\GenreController;
use App\Http\Controllers\Api\Catalog\TrackController;
use App\Http\Controllers\Api\Dedications\DedicationController;
use App\Http\Controllers\Api\Events\EventController;
use App\Http\Controllers\Api\Store\OrderController;
use App\Http\Controllers\Api\Store\ProductController;
use App\Http\Controllers\Api\Users\ArtistController;
use App\Http\Controllers\Api\Vip\VipPlanController;
use App\Http\Controllers\Api\Voting\ContestController;
use App\Http\Controllers\Api\WebhookController;
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
Route::post('/webhooks/payment', [WebhookController::class, 'handle'])
    ->name('webhooks.payment');

// Auth - Get Sanctum token
Route::post('/auth/token', [AuthController::class, 'token'])
    ->name('api.auth.token');

// Catalog - Public read-only
Route::name('api.')->group(function () {
    // Genres
    Route::get('/genres', [GenreController::class, 'index'])->name('genres.index');
    Route::get('/genres/{genre}', [GenreController::class, 'show'])->name('genres.show');

    // Albums
    Route::get('/albums', [AlbumController::class, 'index'])->name('albums.index');
    Route::get('/albums/{album}', [AlbumController::class, 'show'])->name('albums.show');

    // Tracks
    Route::get('/tracks', [TrackController::class, 'index'])->name('tracks.index');
    Route::get('/tracks/{track}', [TrackController::class, 'show'])->name('tracks.show');

    // Artists (public profiles - wallet never exposed)
    Route::get('/artists', [ArtistController::class, 'index'])->name('artists.index');
    Route::get('/artists/{user}', [ArtistController::class, 'show'])->name('artists.show');

    // Store - Products
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

    // VIP Plans
    Route::get('/vip-plans', [VipPlanController::class, 'index'])->name('vip-plans.index');
    Route::get('/vip-plans/{vipPlan}', [VipPlanController::class, 'show'])->name('vip-plans.show');

    // Events (upcoming only)
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');

    // Voting - Contests (active only)
    Route::get('/contests', [ContestController::class, 'index'])->name('contests.index');
    Route::get('/contests/{contest}', [ContestController::class, 'show'])->name('contests.show');

    // Dedications - Available artists
    Route::get('/dedications/artists', [DedicationController::class, 'artists'])->name('dedications.artists');
});

// === Authenticated routes (Sanctum) ===
Route::middleware('auth:sanctum')->name('api.')->group(function () {

    // Current user profile
    Route::get('/user', [AuthController::class, 'user'])->name('user');

    // Dedications - Create a request
    Route::post('/dedications', [DedicationController::class, 'store'])->name('dedications.store');

    // Voting - Cast a vote
    Route::post('/contests/{contest}/vote', [ContestController::class, 'vote'])->name('contests.vote');

    // --- Artist-only routes (require role:artist) ---
    Route::middleware(['role:artist'])->group(function () {

        // Orders
        Route::get('/artist/orders', [OrderController::class, 'index'])->name('artist.orders.index');
        Route::get('/artist/orders/{order}', [OrderController::class, 'show'])->name('artist.orders.show');

        // Events - Full CRUD
        Route::post('/artist/events', [EventController::class, 'store'])->name('artist.events.store');
        Route::put('/artist/events/{event}', [EventController::class, 'update'])->name('artist.events.update');
        Route::delete('/artist/events/{event}', [EventController::class, 'destroy'])->name('artist.events.destroy');

        // VIP Plans - Full CRUD
        Route::post('/artist/vip-plans', [VipPlanController::class, 'store'])->name('artist.vip-plans.store');
        Route::put('/artist/vip-plans/{vipPlan}', [VipPlanController::class, 'update'])->name('artist.vip-plans.update');
        Route::delete('/artist/vip-plans/{vipPlan}', [VipPlanController::class, 'destroy'])->name('artist.vip-plans.destroy');
    });

    // --- Admin-only routes (require role:admin) ---
    Route::middleware(['role:admin'])->group(function () {
        // Contests - Admin CRUD
        Route::post('/admin/contests', [ContestController::class, 'store'])->name('admin.contests.store');
        Route::put('/admin/contests/{contest}', [ContestController::class, 'update'])->name('admin.contests.update');
        Route::delete('/admin/contests/{contest}', [ContestController::class, 'destroy'])->name('admin.contests.destroy');
    });
});
