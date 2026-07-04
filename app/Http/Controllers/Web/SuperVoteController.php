<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Voting\Models\Contest;
use App\Http\Controllers\Controller;

class SuperVoteController extends Controller
{
    /**
     * Display the public SuperVote page.
     */
    public function index()
    {
        $activeContest = Contest::with(['candidates.artist.artistProfile'])
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->orderBy('ends_at')
            ->first();

        $finishedContests = Contest::with(['candidates.artist.artistProfile'])
            ->where('status', 'finished')
            ->orderByDesc('ends_at')
            ->limit(6)
            ->get();

        return view('pages::supervote.index', [
            'activeContest' => $activeContest,
            'finishedContests' => $finishedContests,
        ]);
    }
}
