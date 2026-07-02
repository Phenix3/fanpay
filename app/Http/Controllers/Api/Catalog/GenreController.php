<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Catalog;

use App\Domain\Catalog\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class GenreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $genres = Genre::orderBy('name')->get();

        return response()->json(['data' => $genres]);
    }

    public function show(Genre $genre): JsonResponse
    {
        return response()->json(['data' => $genre]);
    }
}
