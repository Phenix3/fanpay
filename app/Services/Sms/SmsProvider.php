<?php

declare(strict_types=1);

namespace App\Services\Sms;

interface SmsProvider
{
    /**
     * Send an SMS message to the given phone number.
     *
     * @return array{success: bool, message: string, provider_ref?: string}
     */
    public function send(string $phone, string $message): array;
}
