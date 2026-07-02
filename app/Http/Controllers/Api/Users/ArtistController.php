<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Users;

use App\Domain\Users\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ArtistController extends Controller
{
    public function index(): JsonResponse
    {
        $artists = User::where('role', 'artist')
            ->whereHas('artistProfile')
            ->with('artistProfile.genre')
            ->orderBy('name')
            ->paginate(20, ['id', 'name']);

        return response()->json($artists);
    }

    private function safeArtistData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'bio' => $user->artistProfile?->bio,
            'genre' => $user->artistProfile?->genre?->name,
            'avatar' => $user->artistProfile?->avatar,
            'is_verified' => $user->artistProfile?->is_verified,
        ];
    }

    public function show(User $user): JsonResponse
    {
        if (! $user->isArtist()) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $user->load('artistProfile.genre');

        return response()->json(['data' => $this->safeArtistData($user)]);
    }
}
