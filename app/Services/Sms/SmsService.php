<?php

declare(strict_types=1);

namespace App\Services\Sms;

class SmsService
{
    private SmsProvider $provider;

    public function __construct(?SmsProvider $provider = null)
    {
        // Default to Unimatrix in production
        $this->provider = $provider ?? $this->resolveProvider();
    }

    /**
     * Send an OTP message.
     */
    public function sendOtp(string $phone, string $code): bool
    {
        $message = __('messages.otp_message', ['code' => $code]);

        $result = $this->provider->send($phone, $message);

        return $result['success'];
    }

    /**
     * Send a custom message.
     */
    public function send(string $phone, string $message): array
    {
        return $this->provider->send($phone, $message);
    }

    /**
     * Resolve the SMS provider based on config.
     */
    private function resolveProvider(): SmsProvider
    {
        $driver = config('services.sms.driver', 'log');

        return match ($driver) {
            'unimatrix' => app(UnimatrixProvider::class),
            'log' => app(LogProvider::class),
            default => app(LogProvider::class),
        };
    }
}
