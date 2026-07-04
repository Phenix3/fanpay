<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Events\Models\Event;
use App\Http\Controllers\Controller;

class EventController extends Controller
{
    /**
     * Display the specified event.
     */
    public function show(string $slug)
    {
        $event = Event::where('slug', $slug)
            ->with(['artist.artistProfile', 'ticketTypes'])
            ->firstOrFail();

        return view('pages::events.show', [
            'event' => $event,
        ]);
    }
}
