<?php

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Domain\Users\Models\KycVerification;
use App\Domain\Users\Models\User;

class RequestKycAction
{
    /**
     * Submit a KYC verification request for an artist.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data): KycVerification
    {
        // Close any pending KYC requests
        $user->kycVerifications()
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        return KycVerification::create([
            'user_id' => $user->id,
            'id_document' => $data['id_document'] ?? null,
            'id_document_type' => $data['id_document_type'] ?? null,
            'id_number' => $data['id_number'] ?? null,
            'momo_phone' => $data['momo_phone'] ?? $user->phone,
            'momo_operator' => $data['momo_operator'] ?? null,
            'selfie' => $data['selfie'] ?? null,
            'status' => 'pending',
        ]);
    }
}
