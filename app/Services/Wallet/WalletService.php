<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use App\Domain\Users\Models\User;
use App\Domain\Wallet\Models\Transaction;
use App\Domain\Wallet\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletService
{
    /**
     * Credit a user's wallet.
     *
     * @param  array<string, mixed>  $meta
     */
    public function credit(User $user, int $amount, string $source, string $reference, ?string $description = null, array $meta = []): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $source, $reference, $description, $meta) {
            $wallet = $this->getOrCreateWalletWithinTransaction($user);

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore + $amount;

            $wallet->update(['balance' => $balanceAfter]);

            return Transaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'source' => $source,
                'reference' => $reference,
                'description' => $description,
                'status' => 'completed',
                'meta' => $meta,
            ]);
        });
    }

    /**
     * Debit a user's wallet.
     *
     * @param  array<string, mixed>  $meta
     *
     * @throws \RuntimeException if insufficient balance
     */
    public function debit(User $user, int $amount, string $source, string $reference, ?string $description = null, array $meta = []): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $source, $reference, $description, $meta) {
            $wallet = $this->getOrCreateWalletWithinTransaction($user);

            if ($wallet->balance < $amount) {
                throw new \RuntimeException(__('Insufficient balance'));
            }

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore - $amount;

            $wallet->update(['balance' => $balanceAfter]);

            return Transaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'source' => $source,
                'reference' => $reference,
                'description' => $description,
                'status' => 'completed',
                'meta' => $meta,
            ]);
        });
    }

    /**
     * Get or create a wallet WITHIN a transaction (lockForUpdate).
     */
    private function getOrCreateWalletWithinTransaction(User $user): Wallet
    {
        // Try to find existing wallet with lock
        $wallet = Wallet::where('user_id', $user->id)
            ->lockForUpdate()
            ->first();

        if ($wallet) {
            return $wallet;
        }

        // Wallet doesn't exist yet — create it (unique constraint prevents doubles)
        return Wallet::create([
            'user_id' => $user->id,
            'balance' => 0,
            'currency' => 'XAF',
        ]);
    }

    /**
     * Get balance for a user (outside transaction).
     */
    public function getBalance(User $user): int
    {
        $wallet = $this->getOrCreateWallet($user);

        return $wallet->balance;
    }

    /**
     * Get or create a wallet for a user.
     */
    public function getOrCreateWallet(User $user): Wallet
    {
        return $user->wallet ?? Wallet::create([
            'user_id' => $user->id,
            'balance' => 0,
            'currency' => 'XAF',
        ]);
    }

    /**
     * Generate a unique reference for transactions.
     */
    public static function generateReference(string $prefix = 'TXN'): string
    {
        return strtoupper($prefix.'-'.Str::random(16));
    }
}
