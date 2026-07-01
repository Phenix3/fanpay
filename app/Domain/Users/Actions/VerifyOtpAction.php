<?php

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Domain\Users\Models\OtpCode;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\Hash;

class VerifyOtpAction
{
    /**
     * Verify an OTP code for the given phone and purpose.
     *
     * @return array{success: bool, message: string, user?: User}
     */
    public function execute(string $phone, string $code, string $purpose = 'verification'): array
    {
        $otp = OtpCode::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest()
            ->first();

        if (! $otp) {
            return ['success' => false, 'message' => __('auth.otp_invalid')];
        }

        if ($otp->expires_at->isPast()) {
            return ['success' => false, 'message' => __('auth.otp_expired')];
        }

        if ($otp->attempts >= $otp->max_attempts) {
            return ['success' => false, 'message' => __('auth.otp_max_attempts')];
        }

        if (! Hash::check($code, $otp->code)) {
            $otp->increment('attempts');

            return ['success' => false, 'message' => __('auth.otp_invalid')];
        }

        // Mark OTP as verified
        $otp->update([
            'verified_at' => now(),
        ]);

        // If OTP is linked to a user, mark phone as verified
        if ($otp->user_id) {
            $user = User::find($otp->user_id);
            if ($user && ! $user->hasVerifiedPhone()) {
                $user->update(['phone_verified_at' => now()]);
            }

            return ['success' => true, 'message' => __('auth.otp_verified'), 'user' => $user];
        }

        // For registration, find or create user by phone
        $user = User::where('phone', $phone)->first();

        if ($user) {
            $user->update(['phone_verified_at' => now()]);
        }

        return ['success' => true, 'message' => __('auth.otp_verified'), 'user' => $user];
    }
}
