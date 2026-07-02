<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Catalog;

use App\Domain\Catalog\Models\Track;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class TrackController extends Controller
{
    public function index(): JsonResponse
    {
        $tracks = Track::with('artist.artistProfile', 'album')
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return response()->json($tracks);
    }

    public function show(Track $track): JsonResponse
    {
        $track->load(['artist.artistProfile', 'album']);

        return response()->json(['data' => $track]);
    }
}
