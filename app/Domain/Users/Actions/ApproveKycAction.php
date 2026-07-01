<?php

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Domain\Users\Models\KycVerification;
use App\Domain\Users\Models\User;

class ApproveKycAction
{
    /**
     * Approve a KYC verification request.
     */
    public function approve(KycVerification $kyc, User $admin): void
    {
        $kyc->update([
            'status' => 'approved',
            'verified_by' => $admin->id,
            'verified_at' => now(),
        ]);

        // Mark the artist as verified
        $kyc->user->artistProfile?->update(['is_verified' => true]);
    }

    /**
     * Reject a KYC verification request.
     */
    public function reject(KycVerification $kyc, User $admin, string $reason): void
    {
        $kyc->update([
            'status' => 'rejected',
            'verified_by' => $admin->id,
            'verified_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }
}
