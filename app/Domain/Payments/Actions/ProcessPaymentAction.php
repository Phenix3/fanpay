<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Domain\Payments\Models\Payment;
use App\Domain\Users\Models\User;
use App\Services\Payment\CampayService;
use App\Services\Payment\NotchPayService;
use App\Services\Payment\PaymentGateway;
use App\Services\Wallet\CommissionService;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessPaymentAction
{
    private PaymentGateway $primaryGateway;

    private ?PaymentGateway $fallbackGateway;

    private CommissionService $commissionService;

    private WalletService $walletService;

    public function __construct(
        CampayService $campay,
        ?NotchPayService $notchPay = null,
        ?CommissionService $commissionService = null,
        ?WalletService $walletService = null,
    ) {
        $this->primaryGateway = $campay;
        $this->fallbackGateway = $notchPay;
        $this->commissionService = $commissionService ?? app(CommissionService::class);
        $this->walletService = $walletService ?? app(WalletService::class);
    }

    /**
     * Process a payment: calculate fees, create payment record, call gateway.
     *
     * @param  array{user: User, amount: int, phone: string, operator?: string, description?: string, payable_type?: string, payable_id?: int}  $data
     * @return array{success: bool, message: string, payment?: Payment, external_ref?: string}
     */
    public function execute(array $data): array
    {
        $user = $data['user'];
        $amount = $data['amount'];
        $phone = $data['phone'];
        $operator = $data['operator'] ?? null;
        $description = $data['description'] ?? 'FanPay Payment';
        $payableType = $data['payable_type'] ?? null;
        $payableId = $data['payable_id'] ?? null;

        $reference = 'PAY-'.strtoupper(Str::random(20));

        // Calculate MoMo fees
        $fees = $this->commissionService->calculateMomoFees($amount);
        $totalAmount = $this->commissionService->calculateTotalForFan($amount);

        // Create payment record
        $payment = Payment::create([
            'user_id' => $user->id,
            'gateway' => $this->primaryGateway->name(),
            'amount' => $amount,
            'fees' => $fees,
            'phone' => $phone,
            'operator' => $operator,
            'reference' => $reference,
            'status' => 'pending',
        ]);

        // Try primary gateway (Campay)
        $result = $this->primaryGateway->initiateCollect([
            'amount' => $totalAmount,
            'phone' => $phone,
            'reference' => $reference,
            'description' => $description,
        ]);

        if ($result['success']) {
            $payment->update([
                'external_ref' => $result['external_ref'],
                'gateway' => $this->primaryGateway->name(),
            ]);

            return [
                'success' => true,
                'message' => 'Payment initiated',
                'payment' => $payment,
                'external_ref' => $result['external_ref'],
            ];
        }

        // Try fallback (Notch Pay)
        if ($this->fallbackGateway) {
            Log::warning('Primary gateway failed, trying fallback', [
                'reference' => $reference,
                'primary' => $this->primaryGateway->name(),
                'error' => $result['message'],
            ]);

            $fallbackResult = $this->fallbackGateway->initiateCollect([
                'amount' => $totalAmount,
                'phone' => $phone,
                'reference' => $reference,
                'description' => $description,
            ]);

            if ($fallbackResult['success']) {
                $payment->update([
                    'external_ref' => $fallbackResult['external_ref'],
                    'gateway' => $this->fallbackGateway->name(),
                ]);

                return [
                    'success' => true,
                    'message' => 'Payment initiated via fallback',
                    'payment' => $payment,
                    'external_ref' => $fallbackResult['external_ref'],
                ];
            }

            Log::error('Both gateways failed', [
                'reference' => $reference,
                'primary_error' => $result['message'],
                'fallback_error' => $fallbackResult['message'],
            ]);
        }

        // Both gateways failed
        $payment->update(['status' => 'failed']);

        return [
            'success' => false,
            'message' => $result['message'],
            'payment' => $payment,
        ];
    }
}
