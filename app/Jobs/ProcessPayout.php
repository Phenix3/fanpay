<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Payments\Models\Payout;
use App\Services\Payment\CampayService;
use App\Services\Payment\NotchPayService;
use App\Services\Payment\PaymentGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessPayout implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    private Payout $payout;

    /**
     * Create a new job instance.
     */
    public function __construct(Payout $payout)
    {
        $this->payout = $payout;
    }

    /**
     * Execute the job.
     */
    public function handle(CampayService $campay, ?NotchPayService $notchPay = null): void
    {
        $payout = $this->payout;

        // Skip if already processed
        if ($payout->status === 'completed' || $payout->status === 'failed') {
            return;
        }

        $payout->update(['status' => 'processing']);

        // Try primary gateway (Campay)
        $result = $this->sendPayout($campay, $payout);

        if ($result['success']) {
            $payout->update([
                'status' => 'completed',
                'external_ref' => $result['external_ref'],
                'processed_at' => now(),
            ]);

            return;
        }

        // Try fallback (Notch Pay)
        if ($notchPay) {
            Log::warning('Primary payout gateway failed, trying fallback', [
                'reference' => $payout->reference,
                'error' => $result['message'],
            ]);

            $fallbackResult = $this->sendPayout($notchPay, $payout);

            if ($fallbackResult['success']) {
                $payout->update([
                    'status' => 'completed',
                    'external_ref' => $fallbackResult['external_ref'],
                    'processed_at' => now(),
                ]);

                return;
            }

            Log::error('Both payout gateways failed', [
                'reference' => $payout->reference,
                'primary_error' => $result['message'],
                'fallback_error' => $fallbackResult['message'],
            ]);
        }

        // Both failed
        $payout->update(['status' => 'failed']);
    }

    /**
     * Send a payout through a gateway.
     */
    private function sendPayout(PaymentGateway $gateway, Payout $payout): array
    {
        return $gateway->initiatePayout([
            'amount' => $payout->amount,
            'phone' => $payout->phone,
            'reference' => $payout->reference,
            'operator' => $payout->operator,
        ]);
    }
}
