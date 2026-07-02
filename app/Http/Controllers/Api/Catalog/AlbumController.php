<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Catalog;

use App\Domain\Catalog\Models\Album;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AlbumController extends Controller
{
    public function index(): JsonResponse
    {
        $albums = Album::with('artist.artistProfile')
            ->withCount('tracks')
            ->where('is_published', true)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($albums);
    }

    public function show(Album $album): JsonResponse
    {
        if (! $album->is_published) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $album->load(['artist.artistProfile', 'tracks' => fn ($q) => $q->orderBy('track_number')]);

        return response()->json(['data' => $album]);
    }
}
