<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Payments\Models\Payment;
use App\Jobs\ProcessPaymentWebhook;
use Illuminate\Console\Command;

class ReconcilePayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:reconcile {--minutes=30 : Number of minutes to consider as pending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile pending payments that may have missed webhooks';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');

        $cutoff = now()->subMinutes($minutes);

        $pendingPayments = Payment::where('status', 'pending')
            ->where('created_at', '<=', $cutoff)
            ->get();

        $count = $pendingPayments->count();

        $this->info("Found {$count} pending payment(s) older than {$minutes} minute(s).");

        foreach ($pendingPayments as $payment) {
            $this->line("Re-processing payment: {$payment->reference}");

            // Dispatch a new webhook-style job to recheck the status
            ProcessPaymentWebhook::dispatch(
                $payment->external_ref ?? $payment->reference,
                'pending',
                $payment->gateway,
                [],
            );
        }

        $this->info('Reconciliation dispatched.');

        return Command::SUCCESS;
    }
}
