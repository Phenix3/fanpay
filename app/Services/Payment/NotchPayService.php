<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Support\PhoneHelper;
use Illuminate\Support\Facades\Http;

class NotchPayService implements PaymentGateway
{
    private string $baseUrl;

    private string $apiKey;

    private string $encryptionKey;

    public function __construct()
    {
        $this->baseUrl = config('services.notchpay.api_url', 'https://api.notchpay.co');
        $this->apiKey = config('services.notchpay.api_key');
        $this->encryptionKey = config('services.notchpay.encryption_key');
    }

    /**
     * Get the authorization headers.
     */
    private function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
            'X-Notch-Encryption' => $this->encryptionKey ? 'true' : 'false',
        ];
    }

    /**
     * Initiate a payment collection via Notch Pay.
     *
     * @param  array{amount: int, phone: string, reference: string, description?: string}  $data
     * @return array{success: bool, message: string, external_ref?: string, payment_url?: string}
     */
    public function initiateCollect(array $data): array
    {
        try {
            $phone = PhoneHelper::formatCameroon($data['phone']);

            $response = Http::timeout(15)
                ->withHeaders($this->authHeaders())
                ->post("{$this->baseUrl}/v1/payments", [
                    'amount' => $data['amount'],
                    'currency' => 'XAF',
                    'phone' => $phone,
                    'description' => $data['description'] ?? 'FanPay Payment',
                    'reference' => $data['reference'],
                    'callback_url' => route('webhooks.payment'),
                ]);

            if (! $response->successful()) {
                logger()->error('NotchPay collect failed', [
                    'reference' => $data['reference'],
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => $response->json('message') ?? 'NotchPay API error',
                ];
            }

            $body = $response->json('data') ?? $response->json();

            return [
                'success' => true,
                'message' => 'Payment initiated',
                'external_ref' => $body['transaction_id'] ?? $body['reference'] ?? null,
                'payment_url' => $body['authorization_url'] ?? null,
            ];

        } catch (\Exception $e) {
            logger()->error('NotchPay collect exception', [
                'reference' => $data['reference'],
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'NotchPay error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Initiate a payout via Notch Pay.
     *
     * @param  array{amount: int, phone: string, reference: string, operator?: string}  $data
     * @return array{success: bool, message: string, external_ref?: string}
     */
    public function initiatePayout(array $data): array
    {
        try {
            $phone = PhoneHelper::formatCameroon($data['phone']);

            $response = Http::timeout(15)
                ->withHeaders($this->authHeaders())
                ->post("{$this->baseUrl}/v1/payouts", [
                    'amount' => $data['amount'],
                    'currency' => 'XAF',
                    'phone' => $phone,
                    'reference' => $data['reference'],
                    'description' => 'FanPay Payout',
                ]);

            if (! $response->successful()) {
                logger()->error('NotchPay payout failed', [
                    'reference' => $data['reference'],
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => $response->json('message') ?? 'NotchPay API error',
                ];
            }

            $body = $response->json('data') ?? $response->json();

            return [
                'success' => true,
                'message' => 'Payout initiated',
                'external_ref' => $body['transaction_id'] ?? $body['reference'] ?? null,
            ];

        } catch (\Exception $e) {
            logger()->error('NotchPay payout exception', [
                'reference' => $data['reference'],
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'NotchPay error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Verify NotchPay webhook signature.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if (! $this->encryptionKey) {
            return true;
        }

        $computed = hash_hmac('sha256', $payload, $this->encryptionKey);

        return hash_equals($computed, $signature);
    }

    public function name(): string
    {
        return 'notchpay';
    }

    /**
     * Parse NotchPay payment callback/notification.
     *
     * @return array{status: string, reference: string, external_ref: string, amount: int}
     */
    public function parseWebhookData(array $payload): array
    {
        $data = $payload['data'] ?? $payload;

        return [
            'status' => match ($data['status'] ?? '') {
                'success', 'completed' => 'completed',
                'failed' => 'failed',
                'pending' => 'pending',
                default => 'pending',
            },
            'reference' => $data['reference'] ?? '',
            'external_ref' => $data['transaction_id'] ?? $data['reference'] ?? '',
            'amount' => (int) ($data['amount'] ?? 0),
        ];
    }
}
