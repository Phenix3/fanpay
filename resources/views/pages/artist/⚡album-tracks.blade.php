<?php

use App\Domain\Catalog\Models\Album;
use App\Domain\Catalog\Models\Track;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Gestion des morceaux')] class extends Component {

    public function albumTypeLabel(string $type): string
    {
        return match ($type) {
            'album' => __('Album'),
            'single' => __('Single'),
            'ep' => __('EP'),
            'compilation' => __('Compilation'),
            default => $type,
        };
    }

    #[Locked]
    public int $albumId;

    public bool $showForm = false;
    public ?int $editingTrackId = null;
    public string $form_title = '';
    public string $form_track_number = '';
    public string $form_price = '';
    public string $form_duration = '';
    public string $form_lyrics = '';
    public bool $form_is_free = false;
    public bool $form_is_vip_exclusive = false;

    public function mount(int $album): void
    {
        $this->albumId = $album;
    }

    #[Computed]
    public function album(): Album
    {
        return Album::where('artist_id', Auth::id())->findOrFail($this->albumId);
    }

    #[Computed]
    public function tracks()
    {
        return Track::where('album_id', $this->albumId)
            ->orderBy('track_number')
            ->orderBy('created_at')
            ->get();
    }

    protected function rules(): array
    {
        return [
            'form_title' => ['required', 'string', 'max:255'],
            'form_track_number' => ['nullable', 'integer', 'min:1'],
            'form_price' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'form_duration' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'form_lyrics' => ['nullable', 'string', 'max:10000'],
            'form_is_free' => ['boolean'],
            'form_is_vip_exclusive' => ['boolean'],
        ];
    }

    public function createTrack(): void
    {
        $this->resetForm();
        $nextNumber = Track::where('album_id', $this->albumId)->max('track_number') + 1;
        $this->form_track_number = (string) $nextNumber;
        $this->showForm = true;
        $this->editingTrackId = null;
    }

    public function editTrack(int $id): void
    {
        $track = Track::where('album_id', $this->albumId)->findOrFail($id);
        $this->form_title = $track->title;
        $this->form_track_number = (string) $track->track_number;
        $this->form_price = (string) $track->price;
        $this->form_duration = (string) $track->duration;
        $this->form_lyrics = $track->lyrics ?? '';
        $this->form_is_free = $track->is_free;
        $this->form_is_vip_exclusive = $track->is_vip_exclusive;
        $this->editingTrackId = $track->id;
        $this->showForm = true;
    }

    public function saveTrack(): void
    {
        $data = $this->validate();

        $attributes = [
            'album_id' => $this->albumId,
            'artist_id' => Auth::id(),
            'title' => $data['form_title'],
            'track_number' => (int) ($this->form_track_number ?: 0),
            'price' => (int) ($this->form_price ?: 0),
            'duration' => (int) ($this->form_duration ?: 0),
            'lyrics' => $data['form_lyrics'],
            'is_free' => $data['form_is_free'],
            'is_vip_exclusive' => $data['form_is_vip_exclusive'],
        ];

        if ($this->editingTrackId) {
            $track = Track::where('album_id', $this->albumId)->findOrFail($this->editingTrackId);
            $track->update($attributes);
            Flux::toast(variant: 'success', text: __('Morceau mis à jour.'));
        } else {
            Track::create($attributes);
            Flux::toast(variant: 'success', text: __('Morceau ajouté.'));
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function deleteTrack(int $id): void
    {
        $track = Track::where('album_id', $this->albumId)->findOrFail($id);
        $track->delete();
        Flux::toast(variant: 'success', text: __('Morceau supprimé.'));
    }

    public function moveTrackUp(int $id): void
    {
        $track = Track::where('album_id', $this->albumId)->findOrFail($id);
        $prev = Track::where('album_id', $this->albumId)
            ->where('track_number', '<', $track->track_number)
            ->orderBy('track_number', 'desc')
            ->first();

        if ($prev) {
            $temp = $track->track_number;
            $track->update(['track_number' => $prev->track_number]);
            $prev->update(['track_number' => $temp]);
        }
    }

    public function moveTrackDown(int $id): void
    {
        $track = Track::where('album_id', $this->albumId)->findOrFail($id);
        $next = Track::where('album_id', $this->albumId)
            ->where('track_number', '>', $track->track_number)
            ->orderBy('track_number', 'asc')
            ->first();

        if ($next) {
            $temp = $track->track_number;
            $track->update(['track_number' => $next->track_number]);
            $next->update(['track_number' => $temp]);
        }
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'form_title', 'form_track_number', 'form_price', 'form_duration',
            'form_lyrics', 'form_is_free', 'form_is_vip_exclusive', 'editingTrackId',
        ]);
    }

    public function formatDuration(int $seconds): string
    {
        $min = intdiv($seconds, 60);
        $sec = $seconds % 60;
        return sprintf('%d:%02d', $min, $sec);
    }

    public function trackTypeLabel(Track $track): string
    {
        if ($track->is_free) return __('Gratuit');
        if ($track->is_vip_exclusive) return __('VIP');
        if ($track->price > 0) return number_format($track->price).' FCFA';
        return __('Gratuit');
    }

    public function trackTypeColor(Track $track): string
    {
        if ($track->is_free) return 'text-emerald-600 dark:text-emerald-400';
        if ($track->is_vip_exclusive) return 'text-amber-600 dark:text-amber-400';
        if ($track->price > 0) return 'text-zinc-900 dark:text-white';
        return 'text-emerald-600 dark:text-emerald-400';
    }
}; ?>

<div class="space-y-8">
    {{-- En-tête avec retour --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" :href="route('artist.catalog')" wire:navigate icon="arrow-left">
                {{ __('Retour') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ $this->album->title }}</flux:heading>
                <flux:subheading>
                    {{ $this->albumTypeLabel($this->album->type) }}
                    @if ($this->album->price > 0)
                        · {{ number_format($this->album->price) }} FCFA
                    @else
                        · {{ __('Gratuit') }}
                    @endif
                </flux:subheading>
            </div>
        </div>
        <flux:button variant="primary" wire:click="createTrack">
            <flux:icon.plus class="size-4" />
            {{ __('Ajouter un morceau') }}
        </flux:button>
    </div>

    {{-- Formulaire --}}
    @if ($showForm)
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon.musical-note class="size-5 text-zinc-600 dark:text-zinc-400" />
                </div>
                <div>
                    <flux:heading>{{ $editingTrackId ? __('Modifier le morceau') : __('Nouveau morceau') }}</flux:heading>
                    <flux:subheading>{{ $editingTrackId ? __('Modifiez les informations du morceau') : __('Ajoutez un morceau à cet album') }}</flux:subheading>
                </div>
            </div>

            <form wire:submit="saveTrack" class="space-y-5 max-w-2xl">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <flux:input wire:model="form_title" :label="__('Titre')" required placeholder="{{ __('Titre du morceau...') }}" />
                    </div>
                    <flux:input wire:model="form_track_number" :label="__('Piste n°')" type="number" min="1" placeholder="1" />
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <flux:input wire:model="form_price" :label="__('Prix (FCFA)')" type="number" min="0" step="100" placeholder="0" description="{{ __('0 = gratuit') }}" />
                    <flux:input wire:model="form_duration" :label="__('Durée (secondes)')" type="number" min="0" placeholder="240" description="{{ __('240 = 4min') }}" />
                </div>

                <div class="flex items-center gap-6">
                    <flux:switch wire:model="form_is_free" :label="__('Gratuit')" />
                    <flux:switch wire:model="form_is_vip_exclusive" :label="__('Exclusif VIP')" />
                </div>

                <div>
                    <flux:textarea wire:model="form_lyrics" :label="__('Paroles')" rows="4" placeholder="{{ __('Paroles du morceau...') }}" />
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <flux:button variant="primary" type="submit">
                        {{ $editingTrackId ? __('Enregistrer') : __('Ajouter') }}
                    </flux:button>
                    <flux:button variant="ghost" wire:click="cancelForm">
                        {{ __('Annuler') }}
                    </flux:button>
                </div>
            </form>
        </div>
    @endif

    {{-- Upload audio --}}
    <div class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 p-8 text-center dark:border-zinc-600 dark:bg-zinc-800/50">
        <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-white dark:bg-zinc-800">
            <flux:icon.cloud-arrow-up class="size-7 text-zinc-400" />
        </div>
        <p class="font-medium text-zinc-900 dark:text-white">{{ __('Téléversement audio (à venir)') }}</p>
        <flux:text class="mt-1">{{ __('Le téléversement de fichiers audio sera disponible dans une prochaine mise à jour avec l\'intégration Bunny Stream. Pour l\'instant, vous pouvez créer et organiser vos morceaux.') }}</flux:text>
    </div>

    {{-- Liste des morceaux --}}
    <div class="rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <flux:heading>{{ __('Morceaux') }} <span class="text-sm font-normal text-zinc-500">({{ $this->tracks->count() }})</span></flux:heading>
        </div>

        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($this->tracks as $track)
                <div class="flex items-center gap-4 px-6 py-4 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                    {{-- Piste --}}
                    <span class="w-6 text-center text-sm font-medium text-zinc-400">{{ $track->track_number }}</span>

                    {{-- Cover placeholder --}}
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon.musical-note class="size-5 text-zinc-400" />
                    </div>

                    {{-- Infos --}}
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-sm text-zinc-900 truncate dark:text-white">{{ $track->title }}</p>
                        <p class="text-xs text-zinc-500">
                            {{ $this->formatDuration($track->duration) }}
                            <span class="mx-1.5 opacity-50">·</span>
                            <span class="{{ $this->trackTypeColor($track) }}">{{ $this->trackTypeLabel($track) }}</span>
                        </p>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-1">
                        <flux:button
                            variant="ghost"
                            size="sm"
                            icon="chevron-up"
                            wire:click="moveTrackUp({{ $track->id }})"
                            :title="__('Monter')"
                            :disabled="$loop->first"
                            class="{{ $loop->first ? 'opacity-30' : '' }}"
                        />
                        <flux:button
                            variant="ghost"
                            size="sm"
                            icon="chevron-down"
                            wire:click="moveTrackDown({{ $track->id }})"
                            :title="__('Descendre')"
                            :disabled="$loop->last"
                            class="{{ $loop->last ? 'opacity-30' : '' }}"
                        />
                        <flux:button
                            variant="ghost"
                            size="sm"
                            icon="pencil-square"
                            wire:click="editTrack({{ $track->id }})"
                            :title="__('Modifier')"
                        />
                        <flux:button
                            variant="ghost"
                            size="sm"
                            icon="trash"
                            wire:click="deleteTrack({{ $track->id }})"
                            wire:confirm="{{ __('Supprimer ce morceau ?') }}"
                            :title="__('Supprimer')"
                            class="text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50"
                        />
                    </div>
                </div>
            @empty
                <div class="p-12 text-center">
                    <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon.musical-note class="size-7 text-zinc-400" />
                    </div>
                    <p class="font-medium text-zinc-900 dark:text-white">{{ __('Aucun morceau') }}</p>
                    <flux:text class="mt-1">{{ __('Ajoutez votre premier morceau à cet album.') }}</flux:text>
                    <flux:button variant="primary" wire:click="createTrack" class="mt-4">
                        {{ __('Ajouter un morceau') }}
                    </flux:button>
                </div>
            @endforelse
        </div>
    </div>
</div>
