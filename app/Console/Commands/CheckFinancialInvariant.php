<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Wallet\Models\Transaction;
use App\Domain\Wallet\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckFinancialInvariant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'financial:check-invariant';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify that Σ wallet balances = Σ credits - Σ debits';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking financial invariant...');

        // Total of all wallet balances
        $totalBalances = Wallet::sum('balance');

        // Total credits minus total debits from completed transactions
        $totalCredits = Transaction::where('type', 'credit')
            ->where('status', 'completed')
            ->sum('amount');

        $totalDebits = Transaction::where('type', 'debit')
            ->where('status', 'completed')
            ->sum('amount');

        $expectedBalance = $totalCredits - $totalDebits;

        $discrepancy = $totalBalances - $expectedBalance;

        $this->table(
            ['Metric', 'Value (FCFA)'],
            [
                ['Σ Wallet Balances', number_format($totalBalances)],
                ['Σ Credits', number_format($totalCredits)],
                ['Σ Debits', number_format($totalDebits)],
                ['Expected Balance', number_format($expectedBalance)],
                ['Discrepancy', number_format($discrepancy)],
            ]
        );

        if ($discrepancy !== 0) {
            $this->error("DISCREPANCY DETECTED: {$discrepancy} FCFA");

            Log::error('Financial invariant violation', [
                'total_balances' => $totalBalances,
                'total_credits' => $totalCredits,
                'total_debits' => $totalDebits,
                'expected_balance' => $expectedBalance,
                'discrepancy' => $discrepancy,
            ]);

            return Command::FAILURE;
        }

        $this->info('✅ Financial invariant holds. All accounts are balanced.');

        return Command::SUCCESS;
    }
}
