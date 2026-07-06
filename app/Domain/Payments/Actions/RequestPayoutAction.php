<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Domain\Payments\Models\Payout;
use App\Domain\Users\Models\User;
use App\Domain\Various\Models\Setting;
use App\Jobs\ProcessPayout;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Str;

class RequestPayoutAction
{
    private WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Request a payout for an artist.
     *
     * @return array{success: bool, message: string, payout?: Payout}
     */
    public function execute(User $user, int $amount, string $phone, ?string $operator = null): array
    {
        // Verify the user is an artist
        if (! $user->isArtist()) {
            return ['success' => false, 'message' => __('Only artists can request payouts')];
        }

        // Verify KYC is approved
        if (! $user->canRequestPayout()) {
            return ['success' => false, 'message' => __('KYC verification required before requesting a payout')];
        }

        // Verify minimum payout amount
        $minPayout = (int) Setting::get('min_payout_amount', '5000');
        if ($amount < $minPayout) {
            return ['success' => false, 'message' => __('Minimum payout amount is :amount FCFA', ['amount' => number_format($minPayout)])];
        }

        // Verify maximum payout amount
        $maxPayout = (int) Setting::get('max_payout_amount', '500000');
        if ($amount > $maxPayout) {
            return ['success' => false, 'message' => __('Maximum payout amount is :amount FCFA', ['amount' => number_format($maxPayout)])];
        }

        // Verify sufficient balance
        $balance = $this->walletService->getBalance($user);
        if ($balance < $amount) {
            return ['success' => false, 'message' => __('Insufficient balance')];
        }

        // Determine if auto-approval applies
        $autoThreshold = (int) Setting::get('auto_payout_threshold', '50000');
        $status = $amount <= $autoThreshold ? 'approved' : 'pending';

        $reference = 'PO-'.strtoupper(Str::random(20));

        // Debit the wallet
        $this->walletService->debit(
            $user,
            $amount,
            'payout',
            $reference,
            __('Payout request to :phone', ['phone' => $phone])
        );

        $payout = Payout::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'phone' => $phone,
            'operator' => $operator,
            'reference' => $reference,
            'status' => $status,
            'approved_at' => $status === 'approved' ? now() : null,
        ]);

        // If auto-approved, dispatch processing job
        if ($status === 'approved') {
            ProcessPayout::dispatch($payout);
        }

        return [
            'success' => true,
            'message' => $status === 'approved'
                ? __('Payout request approved and being processed')
                : __('Payout request submitted for approval'),
            'payout' => $payout,
        ];
    }
}
