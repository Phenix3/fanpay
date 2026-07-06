<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use App\Domain\Users\Models\User;
use App\Domain\Wallet\Models\RevenueSplit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RevenueSplitService
{
    private CommissionService $commissionService;

    private WalletService $walletService;

    public function __construct(CommissionService $commissionService, WalletService $walletService)
    {
        $this->commissionService = $commissionService;
        $this->walletService = $walletService;
    }

    /**
     * Validate that the splits sum to 100%.
     *
     * @param  array<array{artist_id: int, percentage: float}>  $splits
     *
     * @throws \InvalidArgumentException
     */
    public function validateSplits(array $splits): void
    {
        $total = array_sum(array_column($splits, 'percentage'));

        if (abs($total - 100) > 0.01) {
            throw new \InvalidArgumentException(__('Revenue splits must sum to 100%'));
        }
    }

    /**
     * Save revenue splits for a given content (album, track, video, product).
     *
     * @param  array<array{artist_id: int, percentage: float}>  $splits
     */
    public function saveSplits(Model $content, array $splits): void
    {
        $this->validateSplits($splits);

        // Remove existing splits
        $content->revenueSplits()->delete();

        // Create new splits
        foreach ($splits as $split) {
            RevenueSplit::create([
                'splittable_type' => $content->getMorphClass(),
                'splittable_id' => $content->id,
                'artist_id' => $split['artist_id'],
                'percentage' => $split['percentage'],
            ]);
        }
    }

    /**
     * Distribute revenue among collaborators after a sale.
     *
     * @param  array<string, mixed>  $meta
     */
    public function distribute(Model $content, User $buyer, string $purchaseReference, ?string $description = null, array $meta = []): void
    {
        $splits = $content->revenueSplits;

        if ($splits->isEmpty()) {
            return;
        }

        $price = $content->price;
        $netAmount = $this->commissionService->calculateNetForArtist($price);

        DB::transaction(function () use ($splits, $netAmount, $purchaseReference, $description, $meta) {
            foreach ($splits as $split) {
                $splitArtist = $split->artist;
                $splitAmount = (int) floor($netAmount * $split->percentage / 100);

                if ($splitAmount <= 0) {
                    continue;
                }

                $splitReference = $purchaseReference.'-SPLIT-'.$split->id;

                $this->walletService->credit(
                    $splitArtist,
                    $splitAmount,
                    'sale',
                    $splitReference,
                    $description,
                    array_merge($meta, [
                        'split_id' => $split->id,
                        'split_percentage' => $split->percentage,
                        'original_purchase' => $purchaseReference,
                    ])
                );
            }
        });
    }
}
