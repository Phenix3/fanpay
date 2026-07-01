<?php

declare(strict_types=1);

namespace App\Services\Payment;

interface PaymentGateway
{
    /**
     * Initiate a payment collection (fan pays → platform).
     *
     * @param  array{amount: int, phone: string, reference: string, description?: string}  $data
     * @return array{success: bool, message: string, external_ref?: string, payment_url?: string}
     */
    public function initiateCollect(array $data): array;

    /**
     * Initiate a payout (platform pays → artist).
     *
     * @param  array{amount: int, phone: string, reference: string, operator?: string}  $data
     * @return array{success: bool, message: string, external_ref?: string}
     */
    public function initiatePayout(array $data): array;

    /**
     * Verify the signature of an incoming webhook.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool;

    /**
     * Get the gateway name/identifier.
     */
    public function name(): string;
}
