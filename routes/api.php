<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider and are
| stateless (no session state) by default.
|
*/

// Payment webhooks (no auth - verified by gateway signature)
Route::post('/webhooks/payment', [\App\Http\Controllers\Api\WebhookController::class, 'handle'])
    ->name('webhooks.payment');

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('api.health');
