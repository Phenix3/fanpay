<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessPaymentWebhook;
use App\Services\Payment\CampayService;
use App\Services\Payment\NotchPayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    private CampayService $campay;

    private ?NotchPayService $notchPay;

    public function __construct(CampayService $campay, ?NotchPayService $notchPay = null)
    {
        $this->campay = $campay;
        $this->notchPay = $notchPay;
    }

    /**
     * Handle incoming payment webhooks from Campay or Notch Pay.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $signature = $request->header('Authorization')
            ?? $request->header('X-Notch-Signature')
            ?? '';

        // Detect gateway from payload structure
        $gateway = $this->detectGateway($payload);

        if (! $gateway) {
            logger()->warning('Webhook: unknown gateway', ['payload' => $payload]);

            return response()->json(['status' => 'ignored'], 200);
        }

        // Verify webhook signature
        $body = $request->getContent();
        $verified = $gateway === 'campay'
            ? $this->campay->verifyWebhookSignature($body, $signature)
            : ($this->notchPay?->verifyWebhookSignature($body, $signature) ?? true);

        if (! $verified) {
            logger()->warning('Webhook: invalid signature', [
                'gateway' => $gateway,
                'signature' => $signature,
            ]);

            return response()->json(['status' => 'invalid_signature'], 401);
        }

        // Extract webhook data
        $webhookData = $this->extractWebhookData($gateway, $payload);

        if (! $webhookData) {
            return response()->json(['status' => 'ignored'], 200);
        }

        // Dispatch job for async processing (idempotent via uniqueId)
        ProcessPaymentWebhook::dispatch(
            $webhookData['external_ref'],
            $webhookData['status'],
            $gateway,
            $payload,
        );

        return response()->json(['status' => 'received'], 200);
    }

    /**
     * Detect the payment gateway from the webhook payload.
     */
    private function detectGateway(array $payload): ?string
    {
        // Campay sends 'reference' and 'status' at the top level
        if (isset($payload['reference']) && isset($payload['status']) && ! isset($payload['data'])) {
            return 'campay';
        }

        // Notch Pay wraps data in a 'data' key
        if (isset($payload['data']) && is_array($payload['data'])) {
            return 'notchpay';
        }

        // Notch Pay may also send 'event' key
        if (isset($payload['event']) && isset($payload['data'])) {
            return 'notchpay';
        }

        return null;
    }

    /**
     * Extract standardized webhook data from the payload.
     *
     * @return array{external_ref: string, status: string}|null
     */
    private function extractWebhookData(string $gateway, array $payload): ?array
    {
        if ($gateway === 'campay') {
            // Campay: { reference: "...", status: "SUCCESSFUL"|"FAILED", ... }
            $status = match (strtolower((string) $payload['status'])) {
                'successful', 'success' => 'completed',
                'failed' => 'failed',
                'pending' => 'pending',
                default => 'pending',
            };

            return [
                'external_ref' => (string) ($payload['reference'] ?? ''),
                'status' => $status,
            ];
        }

        if ($gateway === 'notchpay') {
            $data = $payload['data'] ?? $payload;

            $status = match ($data['status'] ?? '') {
                'success', 'completed' => 'completed',
                'failed' => 'failed',
                default => 'pending',
            };

            return [
                'external_ref' => (string) ($data['transaction_id'] ?? $data['reference'] ?? ''),
                'status' => $status,
            ];
        }

        return null;
    }
}
