<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Voting;

use App\Domain\Voting\Models\Candidate;
use App\Domain\Voting\Models\Contest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ContestController extends Controller
{
    public function index(): JsonResponse
    {
        $contests = Contest::where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->with(['candidates.artist.artistProfile'])
            ->get();

        return response()->json(['data' => $contests]);
    }

    public function show(Contest $contest): JsonResponse
    {
        if ($contest->status !== 'active') {
            return response()->json(['message' => 'Contest is not active'], 404);
        }

        $contest->load(['candidates.artist.artistProfile']);

        return response()->json(['data' => $contest]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'vote_price' => ['required', 'integer', 'min:0', 'max:100000'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['sometimes', 'string', 'in:draft,active,finished'],
        ]);

        $contest = Contest::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'vote_price' => $validated['vote_price'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'status' => $validated['status'] ?? 'draft',
        ]);

        return response()->json(['data' => $contest], 201);
    }

    public function update(Request $request, Contest $contest): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'vote_price' => ['sometimes', 'integer', 'min:0', 'max:100000'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date', 'after:starts_at'],
            'status' => ['sometimes', 'string', 'in:draft,active,finished'],
        ]);

        $contest->update($validated);

        return response()->json(['data' => $contest->fresh()]);
    }

    public function destroy(Request $request, Contest $contest): JsonResponse
    {
        $contest->update(['status' => 'finished']);

        return response()->json(['message' => 'Contest finished'], 200);
    }

    public function vote(Request $request, Contest $contest): JsonResponse
    {
        if (! $contest->isActive()) {
            return response()->json(['message' => 'Contest is not active'], 422);
        }

        $validated = $request->validate([
            'candidate_id' => ['required', 'exists:candidates,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $candidate = Candidate::where('contest_id', $contest->id)
            ->findOrFail($validated['candidate_id']);

        $quantity = (int) $validated['quantity'];
        $amount = $quantity * $contest->vote_price;

        $vote = \App\Domain\Voting\Models\Vote::create([
            'candidate_id' => $candidate->id,
            'user_id' => $request->user()?->id,
            'quantity' => $quantity,
            'amount' => $amount,
            'ip_address' => $request->ip(),
        ]);

        $candidate->increment('vote_count', $quantity);

        return response()->json(['data' => $vote], 201);
    }
}
