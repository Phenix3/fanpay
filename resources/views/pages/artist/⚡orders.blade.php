<?php

use App\Domain\Store\Models\Order;
use App\Domain\Store\Models\OrderItem;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Commandes')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $filter_status = '';
    public ?int $viewingOrderId = null;

    protected function rules(): array
    {
        return [
            'filter_status' => ['nullable', 'in:pending,paid,processing,shipped,delivered,cancelled,refunded'],
        ];
    }

    #[Computed]
    public function orders()
    {
        return Order::where('artist_id', Auth::id())
            ->with('user')
            ->when($this->search, fn ($q) => $q->where('reference', 'like', '%'.$this->search.'%'))
            ->when($this->filter_status, fn ($q) => $q->where('status', $this->filter_status))
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    public function viewOrder(int $id): void
    {
        $this->viewingOrderId = $id;
    }

    public function closeOrderDetail(): void
    {
        $this->viewingOrderId = null;
    }

    public function updateStatus(int $id, string $status): void
    {
        $order = Order::where('artist_id', Auth::id())->findOrFail($id);

        $allowedTransitions = [
            'paid' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'delivered', 'cancelled'],
            'shipped' => ['delivered', 'cancelled'],
        ];

        $currentStatus = $order->status;

        // Allow transitioning to the next logical status
        if ($currentStatus === 'paid' && in_array($status, ['processing', 'cancelled'])) {
            $order->update(['status' => $status]);
        } elseif ($currentStatus === 'processing' && in_array($status, ['shipped', 'delivered', 'cancelled'])) {
            $order->update(['status' => $status]);
        } elseif ($currentStatus === 'shipped' && in_array($status, ['delivered', 'cancelled'])) {
            $order->update(['status' => $status]);
        } elseif ($currentStatus === 'pending') {
            $order->update(['status' => 'cancelled']);
        } else {
            Flux::toast(variant: 'danger', text: __('Transition de statut non autorisée.'));
            return;
        }

        $this->closeOrderDetail();
        Flux::toast(variant: 'success', text: __('Commande mise à jour.'));
    }

    #[Computed]
    public function viewingOrder(): ?Order
    {
        if (! $this->viewingOrderId) return null;
        return Order::where('artist_id', Auth::id())
            ->with(['user', 'items.product'])
            ->find($this->viewingOrderId);
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => __('En attente'),
            'paid' => __('Payée'),
            'processing' => __('En préparation'),
            'shipped' => __('Expédiée'),
            'delivered' => __('Livrée'),
            'cancelled' => __('Annulée'),
            'refunded' => __('Remboursée'),
            default => $status,
        };
    }

    public function statusColor(string $status): string
    {
        return match ($status) {
            'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            'paid' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'processing' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
            'shipped' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
            'delivered' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
            'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            'refunded' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-400',
            default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-400',
        };
    }

    public function statusIcon(string $status): string
    {
        return match ($status) {
            'pending' => 'clock',
            'paid' => 'check-circle',
            'processing' => 'arrow-path',
            'shipped' => 'truck',
            'delivered' => 'check-badge',
            'cancelled' => 'x-circle',
            'refunded' => 'arrow-uturn-left',
            default => 'question-mark-circle',
        };
    }

    public function nextStatuses(string $current): array
    {
        return match ($current) {
            'paid' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'delivered', 'cancelled'],
            'shipped' => ['delivered', 'cancelled'],
            'pending' => ['cancelled'],
            default => [],
        };
    }
}; ?>

<div class="space-y-8">
    {{-- En-tête --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Commandes') }}</flux:heading>
            <flux:subheading>{{ __('Gérez les commandes de vos produits') }}</flux:subheading>
        </div>
    </div>

    {{-- Filtres --}}
    <div class="flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-[200px]">
            <flux:input wire:model.live="search" placeholder="{{ __('Rechercher par référence...') }}" icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="filter_status" class="w-44">
            <option value="">{{ __('Tous les statuts') }}</option>
            <option value="pending">{{ __('En attente') }}</option>
            <option value="paid">{{ __('Payée') }}</option>
            <option value="processing">{{ __('En préparation') }}</option>
            <option value="shipped">{{ __('Expédiée') }}</option>
            <option value="delivered">{{ __('Livrée') }}</option>
            <option value="cancelled">{{ __('Annulée') }}</option>
            <option value="refunded">{{ __('Remboursée') }}</option>
        </flux:select>
    </div>

    {{-- Détail d'une commande --}}
    @if ($viewingOrder && $this->viewingOrder)
        <div class="rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <flux:button variant="ghost" wire:click="closeOrderDetail" icon="arrow-left">
                            {{ __('Retour') }}
                        </flux:button>
                        <div>
                            <flux:heading>{{ __('Commande :reference', ['reference' => $this->viewingOrder->reference]) }}</flux:heading>
                            <flux:subheading class="text-xs">{{ $this->viewingOrder->created_at->translatedFormat('d M Y H:i') }}</flux:subheading>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium {{ $this->statusColor($this->viewingOrder->status) }}">
                        <flux:icon :name="$this->statusIcon($this->viewingOrder->status)" class="size-3.5" />
                        {{ $this->statusLabel($this->viewingOrder->status) }}
                    </span>
                </div>
            </div>

            <div class="p-6 space-y-6">
                {{-- Client --}}
                <div>
                    <flux:heading size="sm" class="mb-2">{{ __('Client') }}</flux:heading>
                    <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $this->viewingOrder->user->name }}</p>
                        <p class="text-sm text-zinc-500">{{ $this->viewingOrder->user->email }}</p>
                        @if ($this->viewingOrder->shipping_phone)
                            <p class="text-sm text-zinc-500">{{ $this->viewingOrder->shipping_phone }}</p>
                        @endif
                    </div>
                </div>

                {{-- Adresse de livraison --}}
                @if ($this->viewingOrder->shipping_address)
                    <div>
                        <flux:heading size="sm" class="mb-2">{{ __('Adresse de livraison') }}</flux:heading>
                        <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                            <p class="text-sm text-zinc-900 dark:text-white">{{ $this->viewingOrder->shipping_address }}</p>
                            @if ($this->viewingOrder->shipping_city)
                                <p class="text-sm text-zinc-500">{{ $this->viewingOrder->shipping_city }}</p>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Articles --}}
                <div>
                    <flux:heading size="sm" class="mb-2">{{ __('Articles') }}</flux:heading>
                    <div class="divide-y divide-zinc-100 rounded-xl bg-zinc-50 dark:divide-zinc-700 dark:bg-zinc-800/50">
                        @foreach ($this->viewingOrder->items as $item)
                            <div class="flex items-center justify-between p-4">
                                <div>
                                    <p class="font-medium text-sm text-zinc-900 dark:text-white">{{ $item->product?->name ?? __('Produit') }}</p>
                                    <p class="text-xs text-zinc-500">x{{ $item->qty }}</p>
                                </div>
                                <p class="font-medium text-sm text-zinc-900 dark:text-white">{{ number_format($item->unit_price * $item->qty) }} FCFA</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Résumé financier --}}
                <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <div class="space-y-1.5 text-sm">
                        <div class="flex justify-between">
                            <span class="text-zinc-500">{{ __('Total') }}</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ number_format($this->viewingOrder->total) }} FCFA</span>
                        </div>
                        @if ($this->viewingOrder->fees > 0)
                            <div class="flex justify-between">
                                <span class="text-zinc-500">{{ __('Frais') }}</span>
                                <span class="text-zinc-500">-{{ number_format($this->viewingOrder->fees) }} FCFA</span>
                            </div>
                        @endif
                        @if ($this->viewingOrder->commission > 0)
                            <div class="flex justify-between">
                                <span class="text-zinc-500">{{ __('Commission') }}</span>
                                <span class="text-zinc-500">-{{ number_format($this->viewingOrder->commission) }} FCFA</span>
                            </div>
                        @endif
                        <div class="flex justify-between border-t border-zinc-200 pt-1.5 dark:border-zinc-700">
                            <span class="font-medium">{{ __('Net') }}</span>
                            <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ number_format($this->viewingOrder->net_amount) }} FCFA</span>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                @if (count($this->nextStatuses($this->viewingOrder->status)) > 0)
                    <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <flux:heading size="sm" class="mb-3">{{ __('Mettre à jour le statut') }}</flux:heading>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($this->nextStatuses($this->viewingOrder->status) as $nextStatus)
                                <flux:button
                                    variant="{{ $nextStatus === 'cancelled' ? 'danger' : 'primary' }}"
                                    size="sm"
                                    wire:click="updateStatus({{ $this->viewingOrder->id }}, '{{ $nextStatus }}')"
                                    wire:confirm="{{ $nextStatus === 'cancelled' ? __('Annuler cette commande ?') : '' }}"
                                >
                                    <flux:icon :name="$this->statusIcon($nextStatus)" class="size-4" />
                                    {{ $this->statusLabel($nextStatus) }}
                                </flux:button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Liste des commandes --}}
    @if (! $viewingOrderId)
        <div class="rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->orders as $order)
                    <div class="flex items-center justify-between px-6 py-4 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50 cursor-pointer" wire:click="viewOrder({{ $order->id }})">
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                                <flux:icon.shopping-cart class="size-5 text-zinc-500" />
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-sm text-zinc-900 truncate dark:text-white">{{ $order->reference }}</p>
                                <p class="text-xs text-zinc-500 truncate">
                                    {{ $order->user->name }}
                                    <span class="mx-1.5 opacity-50">·</span>
                                    {{ $order->created_at->translatedFormat('d M Y H:i') }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-sm text-zinc-900 dark:text-white">{{ number_format($order->total) }} FCFA</p>
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium {{ $this->statusColor($order->status) }}">
                                <flux:icon :name="$this->statusIcon($order->status)" class="size-3" />
                                {{ $this->statusLabel($order->status) }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="p-12 text-center">
                        <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon.shopping-cart class="size-7 text-zinc-400" />
                        </div>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ __('Aucune commande') }}</p>
                        <flux:text class="mt-1">{{ __('Les commandes de vos produits apparaîtront ici.') }}</flux:text>
                    </div>
                @endforelse
            </div>

            {{-- Pagination --}}
            @if ($this->orders->hasPages())
                <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    {{ $this->orders->links(data: ['scrollTo' => false]) }}
                </div>
            @endif
        </div>
    @endif
</div>
