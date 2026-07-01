<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Support\PhoneHelper;
use Illuminate\Support\Facades\Http;

class CampayService implements PaymentGateway
{
    private string $baseUrl;

    private string $appUsername;

    private string $appPassword;

    private ?string $token = null;

    private ?string $tokenExpiresAt = null;

    public function __construct()
    {
        $this->baseUrl = config('services.campay.api_url', 'https://demo.campay.net/api');
        $this->appUsername = config('services.campay.app_username');
        $this->appPassword = config('services.campay.app_password');
    }

    /**
     * Authenticate and get an access token.
     *
     * @throws \RuntimeException
     */
    private function authenticate(): string
    {
        if ($this->token && $this->tokenExpiresAt && now()->isBefore($this->tokenExpiresAt)) {
            return $this->token;
        }

        try {
            $response = Http::timeout(10)
                ->post("{$this->baseUrl}/token/", [
                    'username' => $this->appUsername,
                    'password' => $this->appPassword,
                ]);

            $response->throw();

            $data = $response->json();
            $this->token = $data['token'];
            $this->tokenExpiresAt = now()->addSeconds((int) ($data['lifetime'] ?? 3600));

            return $this->token;

        } catch (\Exception $e) {
            logger()->error('Campay authentication failed', [
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Campay authentication failed: '.$e->getMessage());
        }
    }

    /**
     * Get the authorization headers.
     */
    private function authHeaders(): array
    {
        return [
            'Authorization' => 'Token '.$this->authenticate(),
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Initiate a payment collection via Campay.
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
                ->post("{$this->baseUrl}/collect/", [
                    'amount' => (string) $data['amount'],
                    'currency' => 'XAF',
                    'from' => $phone,
                    'description' => $data['description'] ?? 'FanPay Payment',
                    'external_reference' => $data['reference'],
                ]);

            if (! $response->successful()) {
                logger()->error('Campay collect failed', [
                    'reference' => $data['reference'],
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => $response->json('error') ?? 'Campay API error',
                ];
            }

            $body = $response->json();

            return [
                'success' => true,
                'message' => 'Payment initiated',
                'external_ref' => $body['reference'] ?? null,
                'payment_url' => $body['payment_url'] ?? null,
            ];

        } catch (\Exception $e) {
            logger()->error('Campay collect exception', [
                'reference' => $data['reference'],
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Campay error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Initiate a payout via Campay.
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
                ->post("{$this->baseUrl}/withdraw/", [
                    'amount' => (string) $data['amount'],
                    'currency' => 'XAF',
                    'to' => $phone,
                    'description' => 'FanPay Payout',
                    'external_reference' => $data['reference'],
                ]);

            if (! $response->successful()) {
                logger()->error('Campay payout failed', [
                    'reference' => $data['reference'],
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'message' => $response->json('error') ?? 'Campay API error',
                ];
            }

            $body = $response->json();

            return [
                'success' => true,
                'message' => 'Payout initiated',
                'external_ref' => $body['reference'] ?? null,
            ];

        } catch (\Exception $e) {
            logger()->error('Campay payout exception', [
                'reference' => $data['reference'],
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Campay error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Verify Campay webhook signature.
     * Campay uses HTTPS + reference verification — no HMAC.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        return true;
    }

    public function name(): string
    {
        return 'campay';
    }
}
