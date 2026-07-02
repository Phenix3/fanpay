<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Vip;

use App\Domain\Vip\Models\VipPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class VipPlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = VipPlan::with('artist.artistProfile')
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        return response()->json(['data' => $plans]);
    }

    public function show(VipPlan $vipPlan): JsonResponse
    {
        if (! $vipPlan->is_active) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $vipPlan->load('artist.artistProfile');

        return response()->json(['data' => $vipPlan]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'price' => ['required', 'integer', 'min:0', 'max:1000000'],
            'perks' => ['nullable', 'array'],
            'perks.*' => ['string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        $plan = VipPlan::create([
            'artist_id' => $request->user()->id,
            'name' => $validated['name'],
            'duration_days' => $validated['duration_days'],
            'price' => $validated['price'],
            'perks' => $validated['perks'] ?? [],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json(['data' => $plan], 201);
    }

    public function update(Request $request, VipPlan $vipPlan): JsonResponse
    {
        if ($vipPlan->artist_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'duration_days' => ['sometimes', 'integer', 'min:1', 'max:3650'],
            'price' => ['sometimes', 'integer', 'min:0', 'max:1000000'],
            'perks' => ['nullable', 'array'],
            'perks.*' => ['string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        $vipPlan->update($validated);

        return response()->json(['data' => $vipPlan->fresh()]);
    }

    public function destroy(Request $request, VipPlan $vipPlan): JsonResponse
    {
        if ($vipPlan->artist_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($vipPlan->subscriptions()->where('status', 'active')->exists()) {
            return response()->json(['message' => 'Cannot delete plan with active subscriptions'], 409);
        }

        $vipPlan->delete();

        return response()->json(['message' => 'Deleted'], 200);
    }
}
