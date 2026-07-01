<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use App\Domain\Various\Models\Setting;

class CommissionService
{
    private int $commissionRate;

    private int $momoFeeRate;

    public function __construct()
    {
        $this->commissionRate = (int) Setting::get('commission_rate', '10');
        $this->momoFeeRate = (int) Setting::get('momo_fee_rate', '2.5');
    }

    /**
     * Calculate the total amount the fan must pay.
     * Fan pays: price + momo fees.
     */
    public function calculateTotalForFan(int $price): int
    {
        $momoFees = $this->calculateMomoFees($price);

        return $price + $momoFees;
    }

    /**
     * Calculate the MoMo fees for a given price.
     * MoMo fees = price * (momo_fee_rate / 100), rounded up.
     */
    public function calculateMomoFees(int $price): int
    {
        return (int) ceil($price * $this->momoFeeRate / 100);
    }

    /**
     * Calculate the platform commission.
     * Commission = price * (commission_rate / 100), rounded down.
     */
    public function calculateCommission(int $price): int
    {
        return (int) floor($price * $this->commissionRate / 100);
    }

    /**
     * Calculate the net amount for the artist.
     * Net = price - platform commission.
     */
    public function calculateNetForArtist(int $price): int
    {
        return $price - $this->calculateCommission($price);
    }

    /**
     * Get full breakdown.
     *
     * @return array{price: int, momo_fees: int, total_for_fan: int, commission: int, net_for_artist: int}
     */
    public function getBreakdown(int $price): array
    {
        $momoFees = $this->calculateMomoFees($price);
        $commission = $this->calculateCommission($price);

        return [
            'price' => $price,
            'momo_fees' => $momoFees,
            'total_for_fan' => $price + $momoFees,
            'commission' => $commission,
            'net_for_artist' => $price - $commission,
        ];
    }
}
