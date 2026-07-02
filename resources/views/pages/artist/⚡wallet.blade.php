<?php

use App\Domain\Payments\Actions\RequestPayoutAction;
use App\Domain\Payments\Models\Payout;
use App\Domain\Wallet\Models\Transaction;
use App\Domain\Wallet\Models\Wallet;
use App\Domain\Various\Models\Setting;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('My Wallet')] class extends Component {
    use WithPagination;

    public string $payout_amount = '';
    public string $payout_phone = '';
    public string $payout_operator = '';

    /** @var array<string, mixed> */
    public array $stats = [];

    public function mount(): void
    {
        $this->refreshStats();
    }

    public function refreshStats(): void
    {
        $user = Auth::user();
        $wallet = $user->wallet;

        $this->stats = [
            'current_balance' => $wallet?->balance ?? 0,
            'total_earned' => Transaction::where('user_id', $user->id)
                ->where('type', 'credit')
                ->where('status', 'completed')
                ->sum('amount'),
            'total_withdrawn' => Transaction::where('user_id', $user->id)
                ->where('type', 'debit')
                ->where('source', 'payout')
                ->where('status', 'completed')
                ->sum('amount'),
            'pending_payouts' => Payout::where('user_id', $user->id)
                ->where('status', 'pending')
                ->sum('amount'),
            'min_payout' => (int) Setting::get('min_payout_amount', '5000'),
            'max_payout' => (int) Setting::get('max_payout_amount', '500000'),
        ];
    }

    #[Computed]
    public function recentTransactions(): mixed
    {
        return Transaction::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    #[Computed]
    public function recentPayouts(): mixed
    {
        return Payout::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
    }

    public function requestPayout(RequestPayoutAction $action): void
    {
        $this->validate([
            'payout_amount' => ['required', 'numeric', 'integer', 'min:' . $this->stats['min_payout'], 'max:' . $this->stats['max_payout']],
            'payout_phone' => ['required', 'string', 'regex:/^6[0-9]{8}$/'],
            'payout_operator' => ['nullable', 'string', 'in:orange,mtn,camtel'],
        ], [
            'payout_amount.min' => __('withdrawal_min_error', ['amount' => number_format($this->stats['min_payout'])]),
            'payout_amount.max' => __('withdrawal_max_error', ['amount' => number_format($this->stats['max_payout'])]),
            'payout_phone.regex' => __('withdrawal_phone_error'),
        ]);

        $user = Auth::user();
        $amountCents = (int) $this->payout_amount;

        $result = $action->execute($user, $amountCents, $this->payout_phone, $this->payout_operator ?: null);

        if ($result['success']) {
            $this->reset('payout_amount', 'payout_phone', 'payout_operator');
            $this->refreshStats();
            Flux::toast(variant: 'success', text: $result['message']);
        } else {
            Flux::toast(variant: 'danger', text: $result['message']);
        }
    }

    public function transactionTypeLabel(string $type, string $source): string
    {
        return match ($source) {
            'payout' => __('Retrait'),
            'payment' => __('Paiement reçu'),
            'commission' => __('Commission'),
            'purchase' => __('Achat'),
            'refund' => __('Remboursement'),
            default => $type === 'credit' ? __('Crédit') : __('Débit'),
        };
    }

    public function transactionIcon(string $type): string
    {
        return $type === 'credit' ? 'arrow-down-tray' : 'arrow-up-tray';
    }

    public function transactionColor(string $type): string
    {
        return $type === 'credit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
    }

    public function payoutStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => __('En attente'),
            'approved' => __('Approuvé'),
            'processing' => __('En cours'),
            'completed' => __('Effectué'),
            'failed' => __('Échoué'),
            'rejected' => __('Rejeté'),
            default => $status,
        };
    }

    public function payoutStatusColor(string $status): string
    {
        return match ($status) {
            'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            'approved', 'processing' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
            'failed', 'rejected' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-400',
        };
    }
}; ?>

<div class="space-y-8">
    {{-- En-tête --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Mon portefeuille') }}</flux:heading>
            <flux:subheading>{{ __('Gérez vos revenus et effectuez des retraits') }}</flux:subheading>
        </div>
    </div>

    {{-- Cartes de statistiques --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Solde actuel --}}
        <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-gradient-to-br from-zinc-50 to-white p-6 dark:border-zinc-700 dark:from-zinc-800 dark:to-zinc-900">
            <div class="flex items-center justify-between">
                <flux:icon.wallet class="size-5 text-zinc-400" />
                <span class="text-xs font-medium text-zinc-400 uppercase tracking-wider">{{ __('Solde') }}</span>
            </div>
            <p class="mt-4 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                {{ number_format($this->stats['current_balance']) }} <span class="text-lg font-medium text-zinc-500">FCFA</span>
            </p>
            <div class="mt-1 flex items-center gap-1 text-xs text-zinc-500">
                <flux:icon.information-circle class="size-3.5" />
                {{ __('Disponible pour retrait') }}
            </div>
        </div>

        {{-- Total gagné --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:icon.arrow-down-tray class="size-5 text-emerald-500" />
                <span class="text-xs font-medium text-zinc-400 uppercase tracking-wider">{{ __('Gagné') }}</span>
            </div>
            <p class="mt-4 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                {{ number_format($this->stats['total_earned']) }} <span class="text-sm font-medium text-zinc-500">FCFA</span>
            </p>
        </div>

        {{-- Total retiré --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:icon.arrow-up-tray class="size-5 text-red-500" />
                <span class="text-xs font-medium text-zinc-400 uppercase tracking-wider">{{ __('Retiré') }}</span>
            </div>
            <p class="mt-4 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                {{ number_format($this->stats['total_withdrawn']) }} <span class="text-sm font-medium text-zinc-500">FCFA</span>
            </p>
        </div>

        {{-- Retraits en attente --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:icon.clock class="size-5 text-amber-500" />
                <span class="text-xs font-medium text-zinc-400 uppercase tracking-wider">{{ __('En attente') }}</span>
            </div>
            <p class="mt-4 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                {{ number_format($this->stats['pending_payouts']) }} <span class="text-sm font-medium text-zinc-500">FCFA</span>
            </p>
        </div>
    </div>

    {{-- Grille principale : Formulaire de retrait + Transactions récentes --}}
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-5">
        {{-- Formulaire de retrait --}}
        <div class="lg:col-span-2">
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-6 flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon.arrow-up-tray class="size-5 text-zinc-600 dark:text-zinc-400" />
                    </div>
                    <div>
                        <flux:heading>{{ __('Effectuer un retrait') }}</flux:heading>
                        <flux:subheading class="text-xs">
                            {{ __('Min :amount — Max :amount', [
                                'amount' => number_format($this->stats['min_payout']),
                                'amount_max' => number_format($this->stats['max_payout']),
                            ]) }} FCFA
                        </flux:subheading>
                    </div>
                </div>

                <form wire:submit="requestPayout" class="space-y-5">
                    {{-- Montant --}}
                    <div>
                        <flux:input
                            wire:model="payout_amount"
                            :label="__('Montant (FCFA)')"
                            type="number"
                            min="{{ $this->stats['min_payout'] }}"
                            max="{{ $this->stats['max_payout'] }}"
                            step="100"
                            required
                            placeholder="5000"
                        />
                        @error('payout_amount')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </div>

                    {{-- Téléphone --}}
                    <div>
                        <flux:input
                            wire:model="payout_phone"
                            :label="__('Numéro Mobile Money')"
                            type="tel"
                            required
                            placeholder="6XXXXXXXX"
                            description="{{ __('Numéro camerounais (ex: 691234567)') }}"
                        />
                        @error('payout_phone')
                            <flux:error>{{ $message }}</flux:error>
                        @enderror
                    </div>

                    {{-- Opérateur --}}
                    <div>
                        <flux:select wire:model="payout_operator" :label="__('Opérateur')">
                            <option value="">{{ __('Sélectionner...') }}</option>
                            <option value="orange">{{ __('Orange Money') }}</option>
                            <option value="mtn">{{ __('MTN Mobile Money') }}</option>
                            <option value="camtel">{{ __('Camtel Money') }}</option>
                        </flux:select>
                    </div>

                    {{-- Bouton de soumission --}}
                    <div class="pt-2">
                        <flux:button
                            variant="primary"
                            type="submit"
                            class="w-full"
                            wire:loading.attr="disabled"
                        >
                            <span wire:loading.remove>{{ __('Demander le retrait') }}</span>
                            <span wire:loading>{{ __('Traitement en cours...') }}</span>
                        </flux:button>
                    </div>

                    {{-- Infos commissions --}}
                    <flux:text class="text-xs text-center">
                        {{ __('Les frais Mobile Money (2.5%) sont à la charge du bénéficiaire. Aucune commission supplémentaire n\'est appliquée par la plateforme sur les retraits.') }}
                    </flux:text>
                </form>
            </div>
        </div>

        {{-- Transactions récentes --}}
        <div class="lg:col-span-3">
            <div class="rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <div class="flex items-center justify-between">
                        <flux:heading>{{ __('Historique des transactions') }}</flux:heading>
                        <flux:text class="text-xs">{{ __('Les 15 dernières') }}</flux:text>
                    </div>
                </div>

                <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($this->recentTransactions as $txn)
                        <div class="flex items-center justify-between px-6 py-4 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <div class="flex items-center gap-4">
                                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                                    <flux:icon
                                        :name="$this->transactionIcon($txn->type)"
                                        class="size-5 {{ $this->transactionColor($txn->type) }}"
                                    />
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-sm text-zinc-900 dark:text-white truncate">
                                        {{ $this->transactionTypeLabel($txn->type, $txn->source) }}
                                    </p>
                                    <p class="text-xs text-zinc-500 truncate">
                                        {{ $txn->description ?? $txn->reference }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-sm {{ $this->transactionColor($txn->type) }}">
                                    {{ $txn->type === 'credit' ? '+' : '-' }}{{ number_format($txn->amount) }} FCFA
                                </p>
                                <p class="text-xs text-zinc-400">
                                    {{ $txn->created_at->translatedFormat('d M Y H:i') }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="p-12 text-center">
                            <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                                <flux:icon.banknotes class="size-7 text-zinc-400" />
                            </div>
                            <p class="font-medium text-zinc-900 dark:text-white">{{ __('Aucune transaction') }}</p>
                            <flux:text class="mt-1">{{ __('Les transactions apparaîtront ici lorsque vous commencerez à gagner des revenus.') }}</flux:text>
                        </div>
                    @endforelse
                </div>

                {{-- Pagination --}}
                @if ($this->recentTransactions->hasPages())
                    <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        {{ $this->recentTransactions->links(data: ['scrollTo' => false]) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Derniers retraits --}}
    @if ($this->recentPayouts->isNotEmpty())
        <div class="rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <flux:heading>{{ __('Historique des retraits') }}</flux:heading>
            </div>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach ($this->recentPayouts as $payout)
                    <div class="flex items-center justify-between px-6 py-4 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <div class="flex items-center gap-4">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                                <flux:icon.arrow-up-tray class="size-5 text-zinc-500" />
                            </div>
                            <div>
                                <p class="font-medium text-sm text-zinc-900 dark:text-white">
                                    {{ number_format($payout->amount) }} FCFA
                                </p>
                                <p class="text-xs text-zinc-500">
                                    {{ $payout->phone }} · {{ $payout->reference }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $this->payoutStatusColor($payout->status) }}">
                                {{ $this->payoutStatusLabel($payout->status) }}
                            </span>
                            <p class="mt-1 text-xs text-zinc-400">
                                {{ $payout->created_at->translatedFormat('d M Y H:i') }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
