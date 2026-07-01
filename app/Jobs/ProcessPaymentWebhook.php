<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Payments\Models\Payment;
use App\Services\Wallet\CommissionService;
use App\Services\Wallet\RevenueSplitService;
use App\Services\Wallet\WalletService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPaymentWebhook implements ShouldQueue, ShouldBeUnique
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

    private string $externalRef;

    private string $status;

    private string $gateway;

    private array $rawPayload;

    /**
     * Create a new job instance.
     */
    public function __construct(string $externalRef, string $status, string $gateway, array $rawPayload)
    {
        $this->externalRef = $externalRef;
        $this->status = $status;
        $this->gateway = $gateway;
        $this->rawPayload = $rawPayload;
    }

    /**
     * The unique ID of the job (prevents duplicate processing).
     */
    public function uniqueId(): string
    {
        return 'payment-webhook-'.$this->externalRef;
    }

    /**
     * Execute the job.
     */
    public function handle(
        WalletService $walletService,
        CommissionService $commissionService,
        RevenueSplitService $revenueSplitService,
    ): void {
        // Find the payment by external reference
        $payment = Payment::where('external_ref', $this->externalRef)
            ->where('status', 'pending')
            ->first();

        if (! $payment) {
            Log::warning('Webhook: payment not found or already processed', [
                'external_ref' => $this->externalRef,
            ]);

            return;
        }

        // Process based on status
        if ($this->status !== 'completed') {
            $payment->update([
                'status' => $this->status,
                'webhook_payload' => $this->rawPayload,
            ]);

            return;
        }

        DB::transaction(function () use ($payment, $walletService, $commissionService) {
            // Update payment status
            $payment->update([
                'status' => 'completed',
                'paid_at' => now(),
                'webhook_payload' => $this->rawPayload,
            ]);

            $user = $payment->user;

            // Calculate net amount for the seller/artist
            $netAmount = $commissionService->calculateNetForArtist($payment->amount);

            // Credit the user's wallet
            $walletService->credit(
                $user,
                $netAmount,
                'sale',
                $payment->reference,
                __('Payment completed'),
                [
                    'payment_id' => $payment->id,
                    'gateway' => $this->gateway,
                    'external_ref' => $this->externalRef,
                ]
            );
        });
    }
}
