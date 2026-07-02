<?php

declare(strict_types=1);

use App\Domain\Catalog\Models\Album;
use App\Domain\Catalog\Models\Genre;
use App\Domain\Catalog\Models\Track;
use App\Domain\Dedications\Models\Dedication;
use App\Domain\Events\Models\Event;
use App\Domain\Events\Models\TicketType;
use App\Domain\Store\Models\Order;
use App\Domain\Store\Models\OrderItem;
use App\Domain\Store\Models\Product;
use App\Domain\Users\Models\ArtistProfile;
use App\Domain\Users\Models\User;
use App\Domain\Vip\Models\VipPlan;
use App\Domain\Vip\Models\VipSubscription;
use App\Domain\Voting\Models\Candidate;
use App\Domain\Voting\Models\Contest;
use App\Domain\Voting\Models\Vote;
use App\Domain\Wallet\Models\Wallet;
use Illuminate\Support\Str;

// ─── Helpers ───────────────────────────────────────────────────────────────

/**
 * Seed roles & genres before each test.
 * Runs inside the RefreshDatabase transaction so data is always available.
 */
beforeEach(function () {
    app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    (new \Database\Seeders\RolePermissionSeeder)->run();
    (new \Database\Seeders\GenreSeeder)->run();
});

/**
 * Create a user with a given role and assign the Spatie role.
 */
function createUserWithRole(string $role): User
{
    /** @var User $user */
    $user = User::factory()->create(['role' => $role]);
    $user->assignRole($role);

    return $user;
}

/**
 * Create an artist with an ArtistProfile and return the user.
 * Only passes safe fields (name, email) to the User factory.
 */
function createArtistWithProfile(array $overrides = []): User
{
    $genre = Genre::first();

    // Only pass User-fillable fields to the factory (prevent SQL errors from extra columns)
    $userOverrides = array_intersect_key($overrides, array_flip(['name', 'email', 'phone', 'password', 'role', 'locale']));

    /** @var User $artist */
    $artist = User::factory()->artist()->create($userOverrides);
    $artist->assignRole('artist');

    ArtistProfile::create([
        'user_id' => $artist->id,
        'stage_name' => $overrides['stage_name'] ?? $artist->name,
        'slug' => Str::slug($overrides['stage_name'] ?? $artist->name),
        'bio' => 'A talented artist',
        'genre_id' => $genre->id,
        'is_verified' => $overrides['is_verified'] ?? true,
    ]);

    Wallet::create(['user_id' => $artist->id, 'balance' => 0, 'currency' => 'XAF']);

    return $artist;
}

/**
 * Create a Sanctum token header for the given user.
 */
function authHeader(User $user, string $device = 'test-device'): array
{
    $token = $user->createToken($device)->plainTextToken;

    return ['Authorization' => 'Bearer '.$token];
}

/**
 * Assert the response has a JSON API error structure.
 */
function assertJsonError(\Illuminate\Testing\TestResponse $response, int $status): void
{
    $response->assertStatus($status);
    $response->assertJsonStructure(['message']);
}

// ═══════════════════════════════════════════════════════════════════════════
// 1. AUTH
// ═══════════════════════════════════════════════════════════════════════════

describe('Auth endpoints', function () {

    test('POST /api/auth/token — returns token with valid credentials', function () {
        $user = User::factory()->create(['password' => bcrypt('password')]);

        $response = $this->postJson('/api/auth/token', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'phpunit',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']]);
        expect($response['user']['email'])->toBe($user->email);
    });

    test('POST /api/auth/token — fails with invalid password', function () {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/token', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'device_name' => 'phpunit',
        ]);

        assertJsonError($response, 422);
    });

    test('POST /api/auth/token — fails with non-existent email', function () {
        $response = $this->postJson('/api/auth/token', [
            'email' => 'nobody@example.com',
            'password' => 'password',
            'device_name' => 'phpunit',
        ]);

        assertJsonError($response, 422);
    });

    test('POST /api/auth/token — requires device_name', function () {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/token', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['device_name']);
    });

    test('GET /api/user — returns authenticated user profile', function () {
        $artist = createArtistWithProfile();
        $headers = authHeader($artist);

        $response = $this->getJson('/api/user', $headers);

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['id', 'name', 'email']]);
        $response->assertJsonStructure(['data' => ['artist_profile', 'wallet']]);
        expect($response['data']['id'])->toBe($artist->id);
    });

    test('GET /api/user — returns 401 without token', function () {
        $response = $this->getJson('/api/user');

        assertJsonError($response, 401);
    });

    test('GET /api/user — returns 401 with invalid token', function () {
        $response = $this->getJson('/api/user', ['Authorization' => 'Bearer invalid-token']);

        assertJsonError($response, 401);
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 2. PUBLIC ENDPOINTS
// ═══════════════════════════════════════════════════════════════════════════

describe('Public catalog endpoints', function () {

    test('GET /api/health — returns ok', function () {
        $response = $this->getJson('/api/health');

        $response->assertOk();
        $response->assertJsonStructure(['status', 'timestamp']);
        expect($response['status'])->toBe('ok');
    });

    test('GET /api/genres — lists all genres', function () {
        $response = $this->getJson('/api/genres');

        $response->assertOk();
        // GenreSeeder seeds 15 genres
        expect(count($response->json('data')))->toBe(15);
    });

    test('GET /api/genres/{genre} — returns specific genre', function () {
        $genre = Genre::where('slug', 'makossa')->first();

        $response = $this->getJson("/api/genres/{$genre->id}");

        $response->assertOk();
        expect($response['data']['slug'])->toBe('makossa');
    });

    test('GET /api/albums — returns only published albums', function () {
        $artist = createArtistWithProfile();
        Album::create(['artist_id' => $artist->id, 'title' => 'Public Album', 'slug' => 'public-album', 'is_published' => true]);
        Album::create(['artist_id' => $artist->id, 'title' => 'Draft Album', 'slug' => 'draft-album', 'is_published' => false]);

        $response = $this->getJson('/api/albums');

        $response->assertOk();
        expect($response->json('data'))->toHaveLength(1);
        expect($response['data'][0]['title'])->toBe('Public Album');
    });

    test('GET /api/albums/{album} — 404 for unpublished album', function () {
        $artist = createArtistWithProfile();
        $album = Album::create(['artist_id' => $artist->id, 'title' => 'Hidden', 'slug' => 'hidden-album', 'is_published' => false]);

        $response = $this->getJson("/api/albums/{$album->id}");

        assertJsonError($response, 404);
    });

    test('GET /api/artists — returns only artists with profiles', function () {
        $artist = createArtistWithProfile();

        // Create a fan — should not appear
        User::factory()->create(['role' => 'fan']);

        $response = $this->getJson('/api/artists');

        $response->assertOk();
        $artistIds = collect($response->json('data'))->pluck('id');
        expect($artistIds)->toContain($artist->id);
    });

    test('GET /api/artists/{user} — returns safe artist data (no email/wallet)', function () {
        $artist = createArtistWithProfile();

        $response = $this->getJson("/api/artists/{$artist->id}");

        $response->assertOk();
        $data = $response['data'];
        expect($data)->toHaveKeys(['id', 'name', 'bio', 'genre', 'avatar', 'is_verified']);
        expect($data)->not->toHaveKeys(['email', 'phone', 'wallet', 'password']);
    });

    test('GET /api/artists/{user} — 404 for non-artist user', function () {
        $fan = User::factory()->create(['role' => 'fan']);

        $response = $this->getJson("/api/artists/{$fan->id}");

        assertJsonError($response, 404);
    });

    test('GET /api/products — returns only published products', function () {
        $artist = createArtistWithProfile();
        Product::create(['artist_id' => $artist->id, 'name' => 'T-Shirt', 'price' => 5000, 'is_published' => true]);
        Product::create(['artist_id' => $artist->id, 'name' => 'Hidden', 'price' => 3000, 'is_published' => false]);

        $response = $this->getJson('/api/products');

        $response->assertOk();
        expect($response->json('data'))->toHaveLength(1);
    });

    test('GET /api/vip-plans — returns only active plans', function () {
        $artist = createArtistWithProfile();
        VipPlan::create(['artist_id' => $artist->id, 'name' => 'Gold', 'duration_days' => 30, 'price' => 5000, 'is_active' => true]);
        VipPlan::create(['artist_id' => $artist->id, 'name' => 'Inactive', 'duration_days' => 30, 'price' => 3000, 'is_active' => false]);

        $response = $this->getJson('/api/vip-plans');

        $response->assertOk();
        expect($response->json('data'))->toHaveLength(1);
        expect($response['data'][0]['name'])->toBe('Gold');
    });

    test('GET /api/vip-plans/{plan} — 404 for inactive plan', function () {
        $artist = createArtistWithProfile();
        $plan = VipPlan::create(['artist_id' => $artist->id, 'name' => 'Hidden', 'duration_days' => 30, 'price' => 1000, 'is_active' => false]);

        $response = $this->getJson("/api/vip-plans/{$plan->id}");

        assertJsonError($response, 404);
    });

    test('GET /api/events — returns only upcoming published events', function () {
        $artist = createArtistWithProfile();
        Event::create([
            'artist_id' => $artist->id,
            'title' => 'Concert',
            'slug' => 'concert-1',
            'venue' => 'Palais des Sports',
            'date' => now()->addMonth(),
            'is_published' => true,
        ]);
        Event::create([
            'artist_id' => $artist->id,
            'title' => 'Past Event',
            'slug' => 'past-1',
            'venue' => 'Old Venue',
            'date' => now()->subMonth(),
            'is_published' => true,
        ]);

        $response = $this->getJson('/api/events');

        $response->assertOk();
        $titles = collect($response->json('data'))->pluck('title');
        expect($titles)->toContain('Concert');
        expect($titles)->not->toContain('Past Event');
    });

    test('GET /api/tracks/{track} — returns specific track', function () {
        $artist = createArtistWithProfile();
        $track = Track::create([
            'artist_id' => $artist->id,
            'title' => 'Mon hit',
            'duration' => 240,
            'price' => 500,
            'is_free' => false,
        ]);

        $response = $this->getJson("/api/tracks/{$track->id}");

        $response->assertOk();
        expect($response['data']['title'])->toBe('Mon hit');
    });

    test('GET /api/products/{product} — returns published product', function () {
        $artist = createArtistWithProfile();
        $product = Product::create([
            'artist_id' => $artist->id,
            'name' => 'Poster',
            'price' => 3000,
            'is_published' => true,
        ]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertOk();
        expect($response['data']['name'])->toBe('Poster');
    });

    test('GET /api/products/{product} — 404 for unpublished', function () {
        $artist = createArtistWithProfile();
        $product = Product::create([
            'artist_id' => $artist->id,
            'name' => 'Hidden',
            'price' => 1000,
            'is_published' => false,
        ]);

        $response = $this->getJson("/api/products/{$product->id}");

        assertJsonError($response, 404);
    });

    test('GET /api/events/{event} — returns published event', function () {
        $artist = createArtistWithProfile();
        $event = Event::create([
            'artist_id' => $artist->id,
            'title' => 'Big Concert',
            'slug' => 'big-concert',
            'venue' => 'Stadium',
            'date' => now()->addMonth(),
            'is_published' => true,
        ]);

        $response = $this->getJson("/api/events/{$event->id}");

        $response->assertOk();
        expect($response['data']['title'])->toBe('Big Concert');
    });

    test('GET /api/events/{event} — 404 for unpublished', function () {
        $artist = createArtistWithProfile();
        $event = Event::create([
            'artist_id' => $artist->id,
            'title' => 'Hidden Event',
            'slug' => 'hidden-event',
            'venue' => 'Secret',
            'date' => now()->addMonth(),
            'is_published' => false,
        ]);

        $response = $this->getJson("/api/events/{$event->id}");

        assertJsonError($response, 404);
    });

    test('GET /api/contests/{contest} — returns active contest', function () {
        $contest = Contest::create([
            'title' => 'My Contest',
            'vote_price' => 150,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'status' => 'active',
        ]);

        $response = $this->getJson("/api/contests/{$contest->id}");

        $response->assertOk();
        expect($response['data']['title'])->toBe('My Contest');
    });

    test('GET /api/contests/{contest} — 404 for inactive contest', function () {
        $contest = Contest::create([
            'title' => 'Finished Contest',
            'vote_price' => 150,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->subDay(),
            'status' => 'finished',
        ]);

        $response = $this->getJson("/api/contests/{$contest->id}");

        assertJsonError($response, 404);
    });

    test('GET /api/dedications/artists — returns verified artists', function () {
        $artist = createArtistWithProfile();
        // Unverified artist should not appear
        createArtistWithProfile(['is_verified' => false, 'email' => 'unverified@test.com']);

        $response = $this->getJson('/api/dedications/artists');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        expect($ids)->toContain($artist->id);
        // There should be only one (the unverified one is excluded)
        expect($ids)->toHaveLength(1);
    });

    test('GET /api/contests — returns only active contests', function () {
        Contest::create([
            'title' => 'Active Contest',
            'vote_price' => 100,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'status' => 'active',
        ]);
        Contest::create([
            'title' => 'Finished Contest',
            'vote_price' => 100,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->subDay(),
            'status' => 'finished',
        ]);

        $response = $this->getJson('/api/contests');

        $response->assertOk();
        $titles = collect($response->json('data'))->pluck('title');
        expect($titles)->toContain('Active Contest');
        expect($titles)->not->toContain('Finished Contest');
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 3. SANCTUM-AUTHENTICATED ENDPOINTS
// ═══════════════════════════════════════════════════════════════════════════

describe('Sanctum-authenticated endpoints', function () {

    test('POST /api/dedications — fan creates dedication to verified artist', function () {
        $artist = createArtistWithProfile();
        $fan = createUserWithRole('fan');
        $headers = authHeader($fan);

        $response = $this->postJson('/api/dedications', [
            'artist_id' => $artist->id,
            'message_request' => 'Play my favorite song at your next concert!',
            'price' => 5000,
        ], $headers);

        $response->assertCreated();
        $response->assertJsonStructure(['data' => ['id', 'reference', 'status']]);
        expect($response['data']['status'])->toBe('pending');
    });

    test('POST /api/dedications — returns 401 without auth', function () {
        $artist = createArtistWithProfile();

        $response = $this->postJson('/api/dedications', [
            'artist_id' => $artist->id,
            'message_request' => 'A message for you!',
            'price' => 3000,
        ]);

        assertJsonError($response, 401);
    });

    test('POST /api/contests/{contest}/vote — fan casts vote for candidate', function () {
        $artist = createArtistWithProfile();
        $fan = createUserWithRole('fan');
        $contest = Contest::create([
            'title' => 'Vote Contest',
            'vote_price' => 100,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'status' => 'active',
        ]);
        $candidate = Candidate::create(['contest_id' => $contest->id, 'artist_id' => $artist->id]);
        $headers = authHeader($fan);

        $response = $this->postJson("/api/contests/{$contest->id}/vote", [
            'candidate_id' => $candidate->id,
            'quantity' => 5,
        ], $headers);

        $response->assertCreated();
        $response->assertJsonStructure(['data' => ['id', 'quantity', 'amount']]);
        expect($response['data']['quantity'])->toBe(5);
        expect($response['data']['amount'])->toBe(500); // 5 × 100

        // Verify vote count incremented
        $this->assertDatabaseHas('candidates', [
            'id' => $candidate->id,
            'vote_count' => 5,
        ]);
    });

    test('POST /api/contests/{contest}/vote — 422 for inactive contest', function () {
        $artist = createArtistWithProfile();
        $fan = createUserWithRole('fan');
        $contest = Contest::create([
            'title' => 'Finished',
            'vote_price' => 100,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->subDay(),
            'status' => 'finished',
        ]);
        $candidate = Candidate::create(['contest_id' => $contest->id, 'artist_id' => $artist->id]);
        $headers = authHeader($fan);

        $response = $this->postJson("/api/contests/{$contest->id}/vote", [
            'candidate_id' => $candidate->id,
            'quantity' => 1,
        ], $headers);

        assertJsonError($response, 422);
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 4. ARTIST-ONLY ENDPOINTS
// ═══════════════════════════════════════════════════════════════════════════

describe('Artist-only endpoints', function () {

    test('GET /api/artist/orders — returns only own orders', function () {
        $artist = createArtistWithProfile();
        $otherArtist = createArtistWithProfile(['stage_name' => 'Other Artist', 'email' => 'other@test.com']);
        $fan = createUserWithRole('fan');

        // Create orders for both artists
        Order::create(['user_id' => $fan->id, 'artist_id' => $artist->id, 'reference' => 'ORD-001', 'total' => 1000, 'status' => 'completed']);
        Order::create(['user_id' => $fan->id, 'artist_id' => $otherArtist->id, 'reference' => 'ORD-002', 'total' => 2000, 'status' => 'completed']);

        $headers = authHeader($artist);

        $response = $this->getJson('/api/artist/orders', $headers);

        $response->assertOk();
        $refs = collect($response->json('data'))->pluck('reference');
        expect($refs)->toContain('ORD-001');
        expect($refs)->not->toContain('ORD-002');
    });

    test('POST /api/artist/events — artist creates event', function () {
        $artist = createArtistWithProfile();
        $headers = authHeader($artist);

        $response = $this->postJson('/api/artist/events', [
            'title' => 'Live Concert',
            'venue' => 'Yaoundé Conference Center',
            'date' => now()->addMonth()->toDateTimeString(),
            'description' => 'An amazing night of music!',
        ], $headers);

        $response->assertCreated();
        $response->assertJsonStructure(['data' => ['id', 'title', 'slug', 'venue']]);
        expect($response['data']['title'])->toBe('Live Concert');
        expect($response['data']['is_published'])->toBeFalse();
    });

    test('PUT /api/artist/events/{event} — artist updates own event', function () {
        $artist = createArtistWithProfile();
        $event = Event::create([
            'artist_id' => $artist->id,
            'title' => 'Old Title',
            'slug' => 'old-title',
            'venue' => 'Venue',
            'date' => now()->addMonth(),
        ]);
        $headers = authHeader($artist);

        $response = $this->putJson("/api/artist/events/{$event->id}", [
            'title' => 'New Title',
            'is_published' => true,
        ], $headers);

        $response->assertOk();
        expect($response['data']['title'])->toBe('New Title');
        expect($response['data']['is_published'])->toBeTrue();
    });

    test('PUT /api/artist/events/{event} — 403 for another artist\'s event', function () {
        $artist = createArtistWithProfile();
        $otherArtist = createArtistWithProfile(['email' => 'other@test.com', 'stage_name' => 'Other']);
        $event = Event::create([
            'artist_id' => $otherArtist->id,
            'title' => 'Their Event',
            'slug' => 'their-event',
            'venue' => 'Venue',
            'date' => now()->addMonth(),
        ]);
        $headers = authHeader($artist);

        $response = $this->putJson("/api/artist/events/{$event->id}", [
            'title' => 'Hacked Title',
        ], $headers);

        assertJsonError($response, 403);
    });

    test('DELETE /api/artist/events/{event} — artist deletes own event', function () {
        $artist = createArtistWithProfile();
        $event = Event::create([
            'artist_id' => $artist->id,
            'title' => 'To Delete',
            'slug' => 'to-delete',
            'venue' => 'Venue',
            'date' => now()->addMonth(),
        ]);
        $headers = authHeader($artist);

        $response = $this->deleteJson("/api/artist/events/{$event->id}", [], $headers);

        $response->assertOk();
        expect($response['message'])->toBe('Deleted');

        $this->assertSoftDeleted($event);
    });

    test('POST /api/artist/vip-plans — artist creates VIP plan', function () {
        $artist = createArtistWithProfile();
        $headers = authHeader($artist);

        $response = $this->postJson('/api/artist/vip-plans', [
            'name' => 'Gold Plan',
            'duration_days' => 30,
            'price' => 5000,
            'perks' => ['Exclusive content', 'Early access'],
        ], $headers);

        $response->assertCreated();
        expect($response['data']['name'])->toBe('Gold Plan');
        expect($response['data']['is_active'])->toBeTrue();
    });

    test('PUT /api/artist/vip-plans/{plan} — artist updates own plan', function () {
        $artist = createArtistWithProfile();
        $plan = VipPlan::create([
            'artist_id' => $artist->id,
            'name' => 'Old Plan',
            'duration_days' => 30,
            'price' => 3000,
            'is_active' => true,
        ]);
        $headers = authHeader($artist);

        $response = $this->putJson("/api/artist/vip-plans/{$plan->id}", [
            'price' => 5000,
        ], $headers);

        $response->assertOk();
        expect($response['data']['price'])->toBe(5000);
    });

    test('DELETE /api/artist/vip-plans/{plan} — artist deletes plan without subscribers', function () {
        $artist = createArtistWithProfile();
        $plan = VipPlan::create([
            'artist_id' => $artist->id,
            'name' => 'Remove Me',
            'duration_days' => 30,
            'price' => 1000,
            'is_active' => true,
        ]);
        $headers = authHeader($artist);

        $response = $this->deleteJson("/api/artist/vip-plans/{$plan->id}", [], $headers);

        $response->assertOk();
        expect($response['message'])->toBe('Deleted');
        $this->assertSoftDeleted($plan);
    });

    test('DELETE /api/artist/vip-plans/{plan} — 409 with active subscribers', function () {
        $artist = createArtistWithProfile();
        $fan = createUserWithRole('fan');
        $plan = VipPlan::create([
            'artist_id' => $artist->id,
            'name' => 'Popular Plan',
            'duration_days' => 30,
            'price' => 5000,
            'is_active' => true,
        ]);
        VipSubscription::create([
            'plan_id' => $plan->id,
            'user_id' => $fan->id,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
        $headers = authHeader($artist);

        $response = $this->deleteJson("/api/artist/vip-plans/{$plan->id}", [], $headers);

        assertJsonError($response, 409);
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 5. ADMIN-ONLY ENDPOINTS
// ═══════════════════════════════════════════════════════════════════════════

describe('Admin-only endpoints', function () {

    test('POST /api/admin/contests — admin creates contest', function () {
        $admin = createUserWithRole('admin');
        $headers = authHeader($admin);

        $response = $this->postJson('/api/admin/contests', [
            'title' => 'Summer Battle',
            'vote_price' => 200,
            'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->addWeek()->toDateTimeString(),
            'description' => 'The ultimate summer music battle!',
        ], $headers);

        $response->assertCreated();
        expect($response['data']['title'])->toBe('Summer Battle');
        expect($response['data']['status'])->toBe('draft');
    });

    test('PUT /api/admin/contests/{contest} — admin updates contest', function () {
        $admin = createUserWithRole('admin');
        $contest = Contest::create([
            'title' => 'Draft Contest',
            'vote_price' => 100,
            'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->addMonth()->toDateTimeString(),
            'status' => 'draft',
        ]);
        $headers = authHeader($admin);

        $response = $this->putJson("/api/admin/contests/{$contest->id}", [
            'status' => 'active',
            'title' => 'Active Contest Now',
        ], $headers);

        $response->assertOk();
        expect($response['data']['status'])->toBe('active');
        expect($response['data']['title'])->toBe('Active Contest Now');
    });

    test('DELETE /api/admin/contests/{contest} — admin finishes contest', function () {
        $admin = createUserWithRole('admin');
        $contest = Contest::create([
            'title' => 'To Finish',
            'vote_price' => 100,
            'starts_at' => now()->subDay()->toDateTimeString(),
            'ends_at' => now()->addDay()->toDateTimeString(),
            'status' => 'active',
        ]);
        $headers = authHeader($admin);

        $response = $this->deleteJson("/api/admin/contests/{$contest->id}", [], $headers);

        $response->assertOk();
        expect($response['message'])->toBe('Contest finished');

        $this->assertDatabaseHas('contests', [
            'id' => $contest->id,
            'status' => 'finished',
        ]);
    });
});

// ═══════════════════════════════════════════════════════════════════════════
// 6. AUTHORIZATION & SECURITY
// ═══════════════════════════════════════════════════════════════════════════

describe('Authorization & security', function () {

    test('fan cannot access artist routes', function () {
        $fan = createUserWithRole('fan');
        $headers = authHeader($fan);

        $response = $this->postJson('/api/artist/events', [
            'title' => 'Hack',
            'venue' => 'Anywhere',
            'date' => now()->addMonth()->toDateTimeString(),
        ], $headers);

        assertJsonError($response, 403);
    });

    test('fan cannot access admin routes', function () {
        $fan = createUserWithRole('fan');
        $headers = authHeader($fan);

        $response = $this->postJson('/api/admin/contests', [
            'title' => 'Hack Contest',
            'vote_price' => 100,
            'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->addWeek()->toDateTimeString(),
        ], $headers);

        assertJsonError($response, 403);
    });

    test('artist cannot access admin routes', function () {
        $artist = createArtistWithProfile();
        $headers = authHeader($artist);

        $response = $this->postJson('/api/admin/contests', [
            'title' => 'Hack Contest',
            'vote_price' => 100,
            'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->addWeek()->toDateTimeString(),
        ], $headers);

        assertJsonError($response, 403);
    });

    test('dedicated artist cannot access another artist\'s orders', function () {
        $artist1 = createArtistWithProfile(['email' => 'artist1@test.com']);
        $artist2 = createArtistWithProfile(['email' => 'artist2@test.com']);
        $fan = createUserWithRole('fan');

        $order = Order::create([
            'user_id' => $fan->id,
            'artist_id' => $artist2->id,
            'reference' => 'ORD-SECRET',
            'total' => 5000,
            'status' => 'completed',
        ]);

        $headers = authHeader($artist1);
        $response = $this->getJson("/api/artist/orders/{$order->id}", $headers);

        assertJsonError($response, 403);
    });

    test('public endpoints are accessible without auth', function () {
        $response = $this->getJson('/api/health');
        $response->assertOk();

        // Genres already seeded by beforeAll genre creation if any...
        // Just check the health endpoint - it's the simplest public endpoint
        expect($response['status'])->toBe('ok');
    });
});
