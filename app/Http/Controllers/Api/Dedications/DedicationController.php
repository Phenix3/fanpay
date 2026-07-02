<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Dedications;

use App\Domain\Dedications\Models\Dedication;
use App\Domain\Users\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class DedicationController extends Controller
{
    public function artists(): JsonResponse
    {
        $artists = User::where('role', 'artist')
            ->whereHas('artistProfile', fn ($q) => $q->where('is_verified', true))
            ->with('artistProfile')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $artists]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'artist_id' => ['required', 'exists:users,id'],
            'message_request' => ['required', 'string', 'min:10', 'max:2000'],
            'price' => ['required', 'integer', 'min:500', 'max:100000'],
        ]);

        $artist = User::findOrFail($validated['artist_id']);

        if (! $artist->isArtist()) {
            return response()->json(['message' => 'Invalid artist'], 422);
        }

        $dedication = Dedication::create([
            'artist_id' => $artist->id,
            'fan_id' => $request->user()?->id,
            'message_request' => $validated['message_request'],
            'price' => $validated['price'],
            'status' => 'pending',
            'reference' => 'DED-'.strtoupper(Str::random(16)),
        ]);

        return response()->json(['data' => $dedication], 201);
    }
}
