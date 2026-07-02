<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Events;

use App\Domain\Events\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index(): JsonResponse
    {
        $events = Event::with('artist.artistProfile', 'ticketTypes')
            ->where('is_published', true)
            ->whereNotNull('date')
            ->where('date', '>=', now())
            ->orderBy('date')
            ->paginate(20);

        return response()->json($events);
    }

    public function show(Event $event): JsonResponse
    {
        if (! $event->is_published) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $event->load(['artist.artistProfile', 'ticketTypes']);

        return response()->json(['data' => $event]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'venue' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'date' => ['required', 'date', 'after:now'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_published' => ['boolean'],
        ]);

        $event = Event::create([
            'artist_id' => $request->user()->id,
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']).'-'.Str::random(8),
            'venue' => $validated['venue'],
            'address' => $validated['address'] ?? null,
            'date' => $validated['date'],
            'description' => $validated['description'] ?? null,
            'is_published' => $validated['is_published'] ?? false,
        ]);

        return response()->json(['data' => $event], 201);
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        if ($event->artist_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'venue' => ['sometimes', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'date' => ['sometimes', 'date', 'after:now'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_published' => ['boolean'],
        ]);

        $event->update($validated);

        return response()->json(['data' => $event->fresh()]);
    }

    public function destroy(Request $request, Event $event): JsonResponse
    {
        if ($event->artist_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $event->delete();

        return response()->json(['message' => 'Deleted'], 200);
    }
}
