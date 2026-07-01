<?php

declare(strict_types=1);

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

class LogProvider implements SmsProvider
{
    /**
     * Log the SMS instead of sending it (for development).
     *
     * @return array{success: bool, message: string, provider_ref?: string}
     */
    public function send(string $phone, string $message): array
    {
        Log::info('SMS would be sent', [
            'phone' => $phone,
            'message' => $message,
        ]);

        return [
            'success' => true,
            'message' => 'Logged (dev mode)',
            'provider_ref' => 'log-'.now()->timestamp,
        ];
    }
}
