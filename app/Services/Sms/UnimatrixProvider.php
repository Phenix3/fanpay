<?php

declare(strict_types=1);

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;

class UnimatrixProvider implements SmsProvider
{
    private string $apiUrl;

    private string $accessKeyId;

    private string $secretKey;

    public function __construct()
    {
        $this->apiUrl = config('services.unimatrix.api_url', 'https://api.unimatrix.cm/sms/1/text/query');
        $this->accessKeyId = config('services.unimatrix.access_key_id');
        $this->secretKey = config('services.unimatrix.secret_key');
    }

    /**
     * Send an SMS via Unimatrix API.
     *
     * @return array{success: bool, message: string, provider_ref?: string}
     */
    public function send(string $phone, string $message): array
    {
        // Format phone to international format if needed (237 for Cameroon)
        $phone = $this->formatPhone($phone);

        $response = Http::timeout(10)
            ->get($this->apiUrl, [
                'username' => $this->accessKeyId,
                'password' => $this->secretKey,
                'to' => $phone,
                'text' => $message,
                'from' => config('services.unimatrix.sender', 'FanPay'),
            ]);

        if (! $response->successful()) {
            logger()->warning('Unimatrix SMS failed', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['success' => false, 'message' => 'API error: '.$response->status()];
        }

        $body = $response->body();

        return [
            'success' => str_contains($body, 'OK') || str_contains($body, '0'),
            'message' => $response->body(),
            'provider_ref' => $body,
        ];
    }

    /**
     * Format phone number to international format.
     */
    private function formatPhone(string $phone): string
    {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', (string) $phone);

        // If starts with 0, replace with 237
        if (str_starts_with($phone, '0')) {
            $phone = '237'.substr($phone, 1);
        }

        // If it's a 9-digit number starting with 6, 7, 8, 9 (Cameroon mobile)
        if (strlen($phone) === 9 && preg_match('/^[6-9]/', $phone)) {
            $phone = '237'.$phone;
        }

        return $phone;
    }
}
