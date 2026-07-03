<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Catalog\Models\Album;
use App\Domain\Events\Models\Event;
use App\Domain\Users\Models\ArtistProfile;
use App\Domain\Users\Models\User;
use App\Domain\Vip\Models\VipPlan;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class ArtistProfileController extends Controller
{
    /**
     * Display the public artist profile page by slug.
     */
    public function show(string $slug): View
    {
        /** @var ArtistProfile|null */
        $profile = ArtistProfile::query()
            ->where('slug', $slug)
            ->with([
                'user',
                'genre',
            ])
            ->firstOrFail();

        /** @var User $artist */
        $artist = $profile->user;

        if (! $artist->isArtist()) {
            abort(404);
        }

        // Published albums with track count
        $albums = Album::query()
            ->where('artist_id', $artist->id)
            ->where('is_published', true)
            ->withCount('tracks')
            ->orderByDesc('release_date')
            ->get();

        // Upcoming published events
        $events = Event::query()
            ->where('artist_id', $artist->id)
            ->where('is_published', true)
            ->where('date', '>=', now())
            ->withCount('ticketTypes')
            ->orderBy('date')
            ->get();

        // Active VIP plans with subscriber count
        $vipPlans = VipPlan::query()
            ->where('artist_id', $artist->id)
            ->where('is_active', true)
            ->withCount('subscriptions')
            ->get();

        // Parse stats from profile JSON
        $stats = $profile->stats ?? [];

        return view('pages::artists.profile', compact(
            'profile',
            'artist',
            'albums',
            'events',
            'vipPlans',
            'stats',
        ));
    }
}
