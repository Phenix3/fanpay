<?php

use App\Domain\Catalog\Models\Album;
use App\Domain\Catalog\Models\Genre;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Mon catalogue')] class extends Component {
    use WithPagination;

    // Form properties
    public bool $showForm = false;
    public ?int $editingAlbumId = null;
    public string $form_title = '';
    public string $form_type = 'album';
    public string $form_price = '';
    public string $form_description = '';
    public string $form_genre_id = '';
    public bool $form_is_published = false;
    public bool $form_is_vip_exclusive = false;

    public string $search = '';
    public string $filter_type = '';
    public string $filter_status = '';

    protected function rules(): array
    {
        return [
            'form_title' => ['required', 'string', 'max:255'],
            'form_type' => ['required', 'in:album,single,ep,compilation'],
            'form_price' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'form_description' => ['nullable', 'string', 'max:5000'],
            'form_genre_id' => ['nullable', 'exists:genres,id'],
            'form_is_published' => ['boolean'],
            'form_is_vip_exclusive' => ['boolean'],
        ];
    }

    #[Computed]
    public function albums()
    {
        return Album::where('artist_id', Auth::id())
            ->withCount('tracks')
            ->withSum('tracks', 'duration')
            ->when($this->search, fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->filter_type, fn ($q) => $q->where('type', $this->filter_type))
            ->when($this->filter_status !== '', fn ($q) => $q->where('is_published', $this->filter_status === 'published'))
            ->orderBy('created_at', 'desc')
            ->paginate(12);
    }

    #[Computed]
    public function genres()
    {
        return Genre::orderBy('name')->get();
    }

    public function createAlbum(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->editingAlbumId = null;
    }

    public function editAlbum(int $id): void
    {
        $album = Album::where('artist_id', Auth::id())->findOrFail($id);
        $this->form_title = $album->title;
        $this->form_type = $album->type;
        $this->form_price = (string) $album->price;
        $this->form_description = $album->description ?? '';
        $this->form_genre_id = (string) $album->genre_id ?? '';
        $this->form_is_published = $album->is_published;
        $this->form_is_vip_exclusive = $album->is_vip_exclusive;
        $this->editingAlbumId = $album->id;
        $this->showForm = true;
    }

    public function saveAlbum(): void
    {
        $data = $this->validate();

        // Map form fields to model attributes
        $attributes = [
            'artist_id' => Auth::id(),
            'title' => $data['form_title'],
            'type' => $data['form_type'],
            'price' => (int) ($this->form_price ?: 0),
            'description' => $data['form_description'],
            'genre_id' => $data['form_genre_id'] ? (int) $data['form_genre_id'] : null,
            'is_published' => $data['form_is_published'],
            'is_vip_exclusive' => $data['form_is_vip_exclusive'],
        ];

        if (! $this->editingAlbumId) {
            $attributes['slug'] = Str::slug($data['form_title']).'-'.Str::random(8);
        }

        if ($this->editingAlbumId) {
            $album = Album::where('artist_id', Auth::id())->findOrFail($this->editingAlbumId);
            $album->update($attributes);
            Flux::toast(variant: 'success', text: __('Album mis à jour.'));
        } else {
            Album::create($attributes);
            Flux::toast(variant: 'success', text: __('Album créé avec succès.'));
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function togglePublish(int $id): void
    {
        $album = Album::where('artist_id', Auth::id())->findOrFail($id);
        $album->update(['is_published' => !$album->is_published]);
        Flux::toast(variant: 'success', text: $album->is_published ? __('Album publié.') : __('Album masqué.'));
    }

    public function deleteAlbum(int $id): void
    {
        $album = Album::where('artist_id', Auth::id())->findOrFail($id);
        $album->delete();
        Flux::toast(variant: 'success', text: __('Album supprimé.'));
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'form_title', 'form_type', 'form_price', 'form_description',
            'form_genre_id', 'form_is_published', 'form_is_vip_exclusive',
            'editingAlbumId',
        ]);
        $this->form_type = 'album';
    }

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


}; ?>

<div class="space-y-8">
    {{-- En-tête --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Mon catalogue') }}</flux:heading>
            <flux:subheading>{{ __('Gérez vos albums, singles et morceaux') }}</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="createAlbum">
            <flux:icon.plus class="size-4" />
            {{ __('Nouvel album') }}
        </flux:button>
    </div>

    {{-- Filtres --}}
    <div class="flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-[200px]">
            <flux:input wire:model.live="search" placeholder="{{ __('Rechercher un album...') }}" icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="filter_type" class="w-40">
            <option value="">{{ __('Tous les types') }}</option>
            <option value="album">{{ __('Album') }}</option>
            <option value="single">{{ __('Single') }}</option>
            <option value="ep">{{ __('EP') }}</option>
            <option value="compilation">{{ __('Compilation') }}</option>
        </flux:select>
        <flux:select wire:model.live="filter_status" class="w-44">
            <option value="">{{ __('Tous les statuts') }}</option>
            <option value="published">{{ __('Publié') }}</option>
            <option value="draft">{{ __('Brouillon') }}</option>
        </flux:select>
    </div>

    {{-- Formulaire création/édition --}}
    @if ($showForm)
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon.musical-note class="size-5 text-zinc-600 dark:text-zinc-400" />
                </div>
                <div>
                    <flux:heading>{{ $editingAlbumId ? __('Modifier l\'album') : __('Nouvel album') }}</flux:heading>
                    <flux:subheading>{{ $editingAlbumId ? __('Modifiez les informations de votre album') : __('Créez un nouvel album ou single') }}</flux:subheading>
                </div>
            </div>

            <form wire:submit="saveAlbum" class="space-y-5 max-w-2xl">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <flux:input wire:model="form_title" :label="__('Titre')" required placeholder="{{ __('Titre de l\'album...') }}" />
                    </div>

                    <flux:select wire:model="form_type" :label="__('Type')">
                        <option value="album">{{ __('Album') }}</option>
                        <option value="single">{{ __('Single') }}</option>
                        <option value="ep">{{ __('EP') }}</option>
                        <option value="compilation">{{ __('Compilation') }}</option>
                    </flux:select>

                    <flux:input wire:model="form_price" :label="__('Prix (FCFA)')" type="number" min="0" step="100" placeholder="0" description="{{ __('0 = gratuit') }}" />

                    <flux:select wire:model="form_genre_id" :label="__('Genre')">
                        <option value="">{{ __('Sélectionner...') }}</option>
                        @foreach ($this->genres as $genre)
                            <option value="{{ $genre->id }}">{{ $genre->name }}</option>
                        @endforeach
                    </flux:select>

                    <div class="flex items-center gap-4 pt-6">
                        <flux:switch wire:model="form_is_published" :label="__('Publier immédiatement')" />
                        <flux:switch wire:model="form_is_vip_exclusive" :label="__('Exclusif VIP')" />
                    </div>
                </div>

                <div>
                    <flux:textarea wire:model="form_description" :label="__('Description')" rows="3" placeholder="{{ __('Description de l\'album...') }}" />
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <flux:button variant="primary" type="submit">
                        {{ $editingAlbumId ? __('Enregistrer') : __('Créer l\'album') }}
                    </flux:button>
                    <flux:button variant="ghost" wire:click="cancelForm">
                        {{ __('Annuler') }}
                    </flux:button>
                </div>
            </form>
        </div>
    @endif

    {{-- Grille d'albums --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse ($this->albums as $album)
            <div class="group relative rounded-2xl border border-zinc-200 bg-white transition-all hover:shadow-lg hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600">
                {{-- Cover --}}
                <div class="aspect-square overflow-hidden rounded-t-2xl bg-zinc-100 dark:bg-zinc-800">
                    @if ($album->getFirstMediaUrl('cover'))
                        <img src="{{ $album->getFirstMediaUrl('cover') }}" alt="{{ $album->title }}" class="h-full w-full object-cover transition-transform group-hover:scale-105" />
                    @else
                        <div class="flex h-full items-center justify-center">
                            <flux:icon.musical-note class="size-16 text-zinc-300 dark:text-zinc-600" />
                        </div>
                    @endif

                    {{-- Badge type --}}
                    <div class="absolute left-3 top-3">
                        <span class="inline-flex items-center rounded-full bg-white/90 px-2.5 py-0.5 text-xs font-medium text-zinc-700 shadow-sm backdrop-blur-sm dark:bg-zinc-900/90 dark:text-zinc-300">
                            {{ $this->albumTypeLabel($album->type) }}
                        </span>
                    </div>

                    {{-- Statut --}}
                    <div class="absolute right-3 top-3">
                        @if ($album->is_published)
                            <span class="inline-flex items-center rounded-full bg-emerald-500/90 px-2.5 py-0.5 text-xs font-medium text-white shadow-sm backdrop-blur-sm">
                                {{ __('Publié') }}
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-amber-500/90 px-2.5 py-0.5 text-xs font-medium text-white shadow-sm backdrop-blur-sm">
                                {{ __('Brouillon') }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Infos --}}
                <div class="p-4">
                    <h3 class="font-semibold text-zinc-900 truncate dark:text-white">{{ $album->title }}</h3>
                    <div class="mt-1 flex items-center gap-3 text-xs text-zinc-500">
                        <span>{{ $album->tracks_count }} {{ __('morceaux') }}</span>
                        @if ($album->tracks_count > 0)
                            <span>·</span>
                            <span>{{ sprintf('%d:%02d', intdiv($album->tracks_sum_duration, 60), $album->tracks_sum_duration % 60) }}</span>
                        @endif
                    </div>

                    @if ($album->price > 0)
                        <p class="mt-2 text-sm font-medium text-zinc-900 dark:text-white">{{ number_format($album->price) }} FCFA</p>
                    @else
                        <p class="mt-2 text-sm font-medium text-emerald-600 dark:text-emerald-400">{{ __('Gratuit') }}</p>
                    @endif

                    {{-- Actions --}}
                    <div class="mt-4 flex items-center gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                        <flux:button
                            variant="ghost"
                            size="sm"
                            icon="pencil-square"
                            wire:click="editAlbum({{ $album->id }})"
                            :title="__('Modifier')"
                        />
                        <flux:button
                            variant="ghost"
                            size="sm"
                            :icon="$album->is_published ? 'eye-slash' : 'eye'"
                            wire:click="togglePublish({{ $album->id }})"
                            :title="$album->is_published ? __('Masquer') : __('Publier')"
                        />
                        <flux:spacer />
                        <flux:button
                            variant="primary"
                            size="sm"
                            :href="route('artist.catalog.albums.tracks', $album->id)"
                            wire:navigate
                        >
                            {{ __('Gérer les morceaux') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full p-16 text-center">
                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon.musical-note class="size-8 text-zinc-400" />
                </div>
                @if ($this->search || $this->filter_type || $this->filter_status !== '')
                    <p class="font-medium text-zinc-900 dark:text-white">{{ __('Aucun résultat') }}</p>
                    <flux:text class="mt-1">{{ __('Essayez de modifier vos filtres de recherche.') }}</flux:text>
                @else
                    <p class="font-medium text-zinc-900 dark:text-white">{{ __('Bienvenue dans votre catalogue') }}</p>
                    <flux:text class="mt-1">{{ __('Créez votre premier album ou single pour commencer à diffuser votre musique.') }}</flux:text>
                    <flux:button variant="primary" wire:click="createAlbum" class="mt-4">
                        {{ __('Créer mon premier album') }}
                    </flux:button>
                @endif
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($this->albums->hasPages())
        <div class="border-t border-zinc-200 pt-6 dark:border-zinc-700">
            {{ $this->albums->links(data: ['scrollTo' => false]) }}
        </div>
    @endif
</div>
