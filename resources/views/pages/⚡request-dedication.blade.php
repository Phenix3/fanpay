<?php

use App\Domain\Dedications\Models\Dedication;
use App\Domain\Users\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Str;

new #[Title('Demander une dédicace')] class extends Component {
    public ?int $artist_id = null;
    public string $message_request = '';
    public string $price = '';

    #[Computed]
    public function artist()
    {
        if (! $this->artist_id) return null;
        return User::where('role', 'artist')->with('artistProfile')->find($this->artist_id);
    }

    #[Computed]
    public function artists()
    {
        return User::where('role', 'artist')
            ->whereHas('artistProfile', fn ($q) => $q->where('is_verified', true))
            ->with('artistProfile')
            ->orderBy('name')
            ->get();
    }

    protected function rules(): array
    {
        return [
            'artist_id' => ['required', 'exists:users,id'],
            'message_request' => ['required', 'string', 'min:10', 'max:2000'],
            'price' => ['required', 'integer', 'min:500', 'max:100000'],
        ];
    }

    public function submitRequest(): void
    {
        $this->validate();

        if (! Auth::check()) {
            Flux::toast(variant: 'danger', text: __('Vous devez être connecté pour demander une dédicace.'));
            return;
        }

        $artist = User::find($this->artist_id);
        if (! $artist || ! $artist->isArtist()) {
            Flux::toast(variant: 'danger', text: __('Artiste invalide.'));
            return;
        }

        Dedication::create([
            'artist_id' => $artist->id,
            'fan_id' => Auth::id(),
            'message_request' => $this->message_request,
            'price' => (int) $this->price,
            'status' => 'pending',
            'reference' => 'DED-'.strtoupper(Str::random(16)),
        ]);

        $this->reset('message_request', 'price');
        Flux::toast(variant: 'success', text: __('Votre demande de dédicace a été envoyée ! L\'artiste vous répondra bientôt.'));

        // Redirect to artist page
        $this->redirect(route('dashboard'));
    }

    public function selectArtist(int $id): void
    {
        $this->artist_id = $id;
    }
}; ?>

<div class="max-w-3xl mx-auto space-y-8">
    <div class="text-center">
        <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-pink-100 dark:bg-pink-900/30">
            <flux:icon.sparkles class="size-8 text-pink-500" />
        </div>
        <flux:heading size="xl">{{ __('Demander une dédicace personnalisée') }}</flux:heading>
        <flux:subheading>{{ __('Envoyez une demande à votre artiste préféré et recevez un message personnalisé') }}</flux:subheading>
    </div>

    @if (! $this->artist_id)
        {{-- Sélection de l'artiste --}}
        <div>
            <flux:heading size="sm" class="mb-4">{{ __('1. Choisissez un artiste') }}</flux:heading>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($this->artists as $artist)
                    <button
                        wire:click="selectArtist({{ $artist->id }})"
                        class="group rounded-2xl border border-zinc-200 bg-white p-5 text-left transition-all hover:border-pink-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-pink-600"
                    >
                        <div class="flex items-center gap-3">
                            <div class="flex size-12 items-center justify-center rounded-xl bg-pink-100 text-lg font-bold text-pink-600 dark:bg-pink-900/30 dark:text-pink-400">
                                {{ $artist->initials() }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-zinc-900 truncate dark:text-white">
                                    {{ $artist->artistProfile?->stage_name ?? $artist->name }}
                                </p>
                                <p class="text-xs text-zinc-500 truncate">{{ $artist->name }}</p>
                            </div>
                        </div>
                    </button>
                @empty
                    <div class="col-span-full p-12 text-center">
                        <flux:text>{{ __('Aucun artiste disponible pour le moment.') }}</flux:text>
                    </div>
                @endforelse
            </div>
        </div>
    @else
        {{-- Formulaire de demande --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-6 flex items-center gap-3">
                <flux:button variant="ghost" wire:click="$set('artist_id', null)" icon="arrow-left" size="sm">
                    {{ __('Changer d\'artiste') }}
                </flux:button>
                <div class="flex items-center gap-2">
                    <div class="flex size-8 items-center justify-center rounded-lg bg-pink-100 text-sm font-bold text-pink-600 dark:bg-pink-900/30 dark:text-pink-400">
                        {{ $this->artist?->initials() }}
                    </div>
                    <flux:heading size="sm">{{ $this->artist?->artistProfile?->stage_name ?? $this->artist?->name }}</flux:heading>
                </div>
            </div>

            <form wire:submit="submitRequest" class="space-y-5 max-w-xl">
                <flux:textarea
                    wire:model="message_request"
                    :label="__('2. Votre message de dédicace')"
                    rows="4"
                    required
                    minlength="10"
                    placeholder="{{ __('Écrivez votre message personnalisé... Par exemple : \"Joyeux anniversaire à ma sœur qui t\'adore !\"') }}"
                    description="{{ __('Soyez précis : l\'artiste lira votre message et y répondra personnellement.') }}"
                />

                <div>
                    <flux:input
                        wire:model="price"
                        :label="__('3. Votre contribution (FCFA)')"
                        type="number"
                        min="500"
                        max="100000"
                        step="500"
                        required
                        placeholder="5000"
                        description="{{ __('Minimum 500 FCFA. L\'artiste reçoit 90% après déduction des frais.') }}"
                    />
                </div>

                <flux:button variant="primary" type="submit" class="w-full sm:w-auto">
                    <flux:icon.sparkles class="size-4" />
                    {{ __('Envoyer ma demande') }}
                </flux:button>
            </form>
        </div>
    @endif
</div>
