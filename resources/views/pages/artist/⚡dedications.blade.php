<?php

use App\Domain\Dedications\Models\Dedication;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Dédicaces')] class extends Component {
    use WithPagination;

    public string $filter_status = '';

    // Response form
    public ?int $respondingToId = null;
    public string $response_type = 'text';
    public string $response_message = '';

    protected function rules(): array
    {
        return [
            'response_type' => ['required', 'in:text,audio,video'],
            'response_message' => ['required_if:response_type,text', 'string', 'max:5000'],
        ];
    }

    #[Computed]
    public function dedications()
    {
        return Dedication::where('artist_id', Auth::id())
            ->with('fan')
            ->when($this->filter_status, fn ($q) => $q->where('status', $this->filter_status))
            ->orderByRaw("FIELD(status, 'pending', 'paid', 'completed', 'cancelled')")
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    #[Computed]
    public function stats(): array
    {
        $userId = Auth::id();
        return [
            'pending' => Dedication::where('artist_id', $userId)->where('status', 'pending')->count(),
            'paid' => Dedication::where('artist_id', $userId)->where('status', 'paid')->count(),
            'completed' => Dedication::where('artist_id', $userId)->where('status', 'completed')->count(),
            'total_earned' => Dedication::where('artist_id', $userId)->where('status', 'completed')->sum('price'),
        ];
    }

    public function showRespond(int $id): void
    {
        $dedication = Dedication::where('artist_id', Auth::id())->findOrFail($id);
        $this->respondingToId = $dedication->id;
        $this->response_type = 'text';
        $this->response_message = '';
    }

    public function cancelRespond(): void
    {
        $this->respondingToId = null;
        $this->reset('response_type', 'response_message');
    }

    public function submitResponse(): void
    {
        $data = $this->validate();
        $dedication = Dedication::where('artist_id', Auth::id())->findOrFail($this->respondingToId);

        $dedication->update([
            'response_type' => $data['response_type'],
            'response_message' => $data['response_type'] === 'text' ? $data['response_message'] : null,
            'status' => 'completed',
            'delivered_at' => now(),
        ]);

        $this->cancelRespond();
        Flux::toast(variant: 'success', text: __('Dédicace envoyée au fan !'));
    }

    public function markPaid(int $id): void
    {
        $dedication = Dedication::where('artist_id', Auth::id())->findOrFail($id);
        $dedication->update(['status' => 'paid']);
        Flux::toast(variant: 'success', text: __('Dédicace marquée comme payée.'));
    }

    public function cancelDedication(int $id): void
    {
        $dedication = Dedication::where('artist_id', Auth::id())->findOrFail($id);
        $dedication->update(['status' => 'cancelled']);
        Flux::toast(variant: 'success', text: __('Dédicace annulée.'));
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => __('En attente de paiement'),
            'paid' => __('Payée - À répondre'),
            'completed' => __('Complétée'),
            'cancelled' => __('Annulée'),
            default => $status,
        };
    }

    public function statusColor(string $status): string
    {
        return match ($status) {
            'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            'paid' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
            'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-400',
        };
    }

    public function responseTypeIcon(string $type): string
    {
        return match ($type) {
            'text' => 'chat-bubble-left',
            'audio' => 'microphone',
            'video' => 'video-camera',
            default => 'chat-bubble-left',
        };
    }
}; ?>

<div class="space-y-8">
    {{-- En-tête --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Dédicaces personnalisées') }}</flux:heading>
            <flux:subheading>{{ __('Répondez aux demandes de dédicace de vos fans') }}</flux:subheading>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-4">
        <div class="rounded-2xl border border-zinc-200 bg-gradient-to-br from-pink-50 to-white p-6 dark:border-zinc-700 dark:from-pink-950/20 dark:to-zinc-900">
            <div class="flex items-center justify-between">
                <flux:icon.clock class="size-5 text-pink-500" />
                <span class="text-xs font-medium text-zinc-400 uppercase tracking-wider">{{ __('En attente') }}</span>
            </div>
            <p class="mt-4 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $this->stats['pending'] }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:icon.credit-card class="size-5 text-blue-500" />
                <span class="text-xs font-medium text-zinc-400 uppercase tracking-wider">{{ __('À répondre') }}</span>
            </div>
            <p class="mt-4 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $this->stats['paid'] }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:icon.check-badge class="size-5 text-emerald-500" />
                <span class="text-xs font-medium text-zinc-400 uppercase tracking-wider">{{ __('Complétées') }}</span>
            </div>
            <p class="mt-4 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $this->stats['completed'] }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:icon.arrow-trending-up class="size-5 text-emerald-500" />
                <span class="text-xs font-medium text-zinc-400 uppercase tracking-wider">{{ __('Gagné') }}</span>
            </div>
            <p class="mt-4 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ number_format($this->stats['total_earned']) }}</p>
        </div>
    </div>

    {{-- Filtre --}}
    <div class="flex items-center gap-4">
        <flux:select wire:model.live="filter_status" class="w-52">
            <option value="">{{ __('Toutes les dédicaces') }}</option>
            <option value="pending">{{ __('En attente de paiement') }}</option>
            <option value="paid">{{ __('Payée - À répondre') }}</option>
            <option value="completed">{{ __('Complétée') }}</option>
            <option value="cancelled">{{ __('Annulée') }}</option>
        </flux:select>
    </div>

    {{-- Formulaire de réponse --}}
    @if ($respondingToId)
        @php
            $current = Dedication::find($respondingToId);
        @endphp
        @if ($current)
            <div class="rounded-2xl border border-zinc-200 bg-gradient-to-br from-pink-50 to-white p-6 dark:border-zinc-700 dark:from-pink-950/20 dark:to-zinc-900">
                <div class="mb-4 flex items-center gap-3">
                    <flux:icon.sparkles class="size-5 text-pink-500" />
                    <div>
                        <flux:heading>{{ __('Répondre à la dédicace') }}</flux:heading>
                        <flux:subheading class="text-xs">{{ __('Fan :') }} {{ $current->fan?->name ?? __('Anonyme') }} · {{ number_format($current->price) }} FCFA</flux:subheading>
                    </div>
                </div>

                {{-- Message du fan --}}
                <div class="mb-4 rounded-xl bg-white p-4 italic text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                    "{{ $current->message_request }}"
                </div>

                <form wire:submit="submitResponse" class="space-y-4 max-w-xl">
                    <flux:select wire:model="response_type" :label="__('Type de réponse')">
                        <option value="text">{{ __('Message texte') }}</option>
                        <option value="audio">{{ __('Message audio') }}</option>
                        <option value="video">{{ __('Message vidéo') }}</option>
                    </flux:select>

                    @if ($response_type === 'text')
                        <flux:textarea wire:model="response_message" :label="__('Votre message')" rows="4" required placeholder="{{ __('Écrivez votre dédicace personnalisée...') }}" />
                    @else
                        <div class="rounded-xl border border-dashed border-zinc-300 bg-white p-6 text-center dark:border-zinc-600 dark:bg-zinc-800">
                            <flux:icon.microphone class="mx-auto size-8 text-zinc-400" />
                            <p class="mt-2 font-medium text-zinc-900 dark:text-white">{{ __('Enregistrement audio/vidéo') }}</p>
                            <flux:text class="mt-1">{{ __('Le téléversement de médias sera disponible avec l\'intégration Bunny Stream.') }}</flux:text>
                        </div>
                    @endif

                    <div class="flex items-center gap-3">
                        <flux:button variant="primary" type="submit">
                            <flux:icon.sparkles class="size-4" />
                            {{ __('Envoyer la dédicace') }}
                        </flux:button>
                        <flux:button variant="ghost" wire:click="cancelRespond">
                            {{ __('Annuler') }}
                        </flux:button>
                    </div>
                </form>
            </div>
        @endif
    @endif

    {{-- Liste des dédicaces --}}
    <div class="rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($this->dedications as $dedication)
                <div class="px-6 py-5 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4 min-w-0">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-pink-100 dark:bg-pink-900/30">
                                <flux:icon.sparkles class="size-5 text-pink-500" />
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="font-medium text-sm text-zinc-900 dark:text-white">
                                        {{ $dedication->fan?->name ?? __('Anonyme') }}
                                    </p>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $this->statusColor($dedication->status) }}">
                                        {{ $this->statusLabel($dedication->status) }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400 line-clamp-2">
                                    "{{ $dedication->message_request }}"
                                </p>
                                <div class="mt-2 flex items-center gap-3 text-xs text-zinc-500">
                                    <span>{{ number_format($dedication->price) }} FCFA</span>
                                    <span>·</span>
                                    <span>{{ $dedication->created_at->translatedFormat('d M Y H:i') }}</span>
                                    @if ($dedication->delivered_at)
                                        <span>·</span>
                                        <span class="text-emerald-500">{{ __('Délivrée le :date', ['date' => $dedication->delivered_at->translatedFormat('d M Y')]) }}</span>
                                    @endif
                                </div>

                                @if ($dedication->response_message)
                                    <div class="mt-3 rounded-lg bg-zinc-50 p-3 text-sm text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                                        <div class="mb-1 flex items-center gap-1.5 text-xs text-zinc-500">
                                            <flux:icon.chat-bubble-left class="size-3.5" />
                                            {{ __('Votre réponse :') }}
                                        </div>
                                        {{ $dedication->response_message }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-1 shrink-0 ml-4">
                            @if ($dedication->status === 'pending')
                                <flux:button variant="ghost" size="sm" icon="credit-card" wire:click="markPaid({{ $dedication->id }})" :title="__('Marquer payée')" class="text-blue-500 hover:text-blue-600" />
                                <flux:button variant="ghost" size="sm" icon="x-circle" wire:click="cancelDedication({{ $dedication->id }})" wire:confirm="{{ __('Annuler cette dédicace ?') }}" :title="__('Annuler')" class="text-red-500 hover:text-red-600" />
                            @endif
                            @if ($dedication->status === 'paid')
                                <flux:button variant="primary" size="sm" wire:click="showRespond({{ $dedication->id }})">
                                    <flux:icon.sparkles class="size-4" />
                                    {{ __('Répondre') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center">
                    <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-pink-100 dark:bg-pink-900/30">
                        <flux:icon.sparkles class="size-7 text-pink-500" />
                    </div>
                    <p class="font-medium text-zinc-900 dark:text-white">{{ __('Aucune demande de dédicace') }}</p>
                    <flux:text class="mt-1">{{ __('Les demandes de dédicace de vos fans apparaîtront ici.') }}</flux:text>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if ($this->dedications->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $this->dedications->links(data: ['scrollTo' => false]) }}
            </div>
        @endif
    </div>
</div>
