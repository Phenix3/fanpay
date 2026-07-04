<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Events\Models\Event;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EventListController extends Controller
{
    /**
     * Display a listing of public events.
     */
    public function index(Request $request)
    {
        $query = Event::with(['artist.artistProfile', 'ticketTypes'])
            ->where('is_published', true)
            ->whereNotNull('date');

        // Filter by upcoming / past
        $period = $request->input('period', 'upcoming');
        if ($period === 'upcoming') {
            $query->where('date', '>=', now())
                ->orderBy('date');
        } elseif ($period === 'past') {
            $query->where('date', '<', now())
                ->orderByDesc('date');
        }

        // Optional search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('venue', 'like', "%{$search}%")
                  ->orWhereHas('artist', function ($art) use ($search) {
                      $art->where('name', 'like', "%{$search}%")
                          ->orWhereHas('artistProfile', function ($profile) use ($search) {
                              $profile->where('stage_name', 'like', "%{$search}%");
                          });
                  });
            });
        }

        $events = $query->paginate(12);

        return view('pages::events.index', [
            'events' => $events,
            'currentPeriod' => $period,
        ]);
    }
}
