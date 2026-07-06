<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ArtistListController extends Controller
{
    /**
     * Display a listing of all artists.
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'artist')
            ->whereHas('artistProfile')
            ->with('artistProfile.genre');

        // Optional genre filter
        if ($request->filled('genre')) {
            $query->whereHas('artistProfile.genre', function ($q) use ($request) {
                $q->where('slug', $request->genre);
            });
        }

        // Optional search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('artistProfile', function ($profile) use ($search) {
                        $profile->where('stage_name', 'like', "%{$search}%");
                    });
            });
        }

        $artists = $query->orderBy('name')->paginate(24);

        return view('pages::artists.index', [
            'artists' => $artists,
        ]);
    }
}
