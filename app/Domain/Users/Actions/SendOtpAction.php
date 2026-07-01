<?php

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Domain\Users\Models\OtpCode;
use App\Domain\Users\Models\User;
use App\Domain\Various\Models\Setting;
use App\Jobs\SendOtpSms;
use Illuminate\Support\Facades\Hash;

class SendOtpAction
{
    /**
     * Generate and send an OTP code to the given phone number.
     */
    public function execute(string $phone, string $purpose = 'verification', ?User $user = null): OtpCode
    {
        // Generate a 6-digit code
        $code = (string) random_int(100000, 999999);

        // Hash the code before storing
        $otp = OtpCode::create([
            'user_id' => $user?->id,
            'phone' => $phone,
            'code' => Hash::make($code),
            'purpose' => $purpose,
            'attempts' => 0,
            'max_attempts' => 3,
            'expires_at' => now()->addMinutes((int) Setting::get('otp_expiry_minutes', '5')),
        ]);

        // Dispatch SMS job
        SendOtpSms::dispatch($phone, $code, $purpose);

        return $otp;
    }
}
