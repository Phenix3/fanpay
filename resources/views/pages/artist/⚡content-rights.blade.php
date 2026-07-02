<?php

use App\Domain\Catalog\Models\Album;
use App\Domain\Catalog\Models\Track;
use App\Domain\Content\Models\ContentRight;
use App\Domain\Users\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Droits d\'auteur')] class extends Component {
    public int $currentStep = 1;
    public bool $socam_member = false;
    public array $declaredTrackIds = [];
    public array $declaredAlbumIds = [];

    #[Computed]
    public function tracks()
    {
        return Track::where('artist_id', Auth::id())
            ->with('album')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function albums()
    {
        return Album::where('artist_id', Auth::id())
            ->withCount('tracks')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function declaredRights()
    {
        return ContentRight::where('artist_id', Auth::id())->get();
    }

    #[Computed]
    public function declaredTrackIdsSet(): array
    {
        return $this->declaredRights
            ->where('rightable_type', (new Track)->getMorphClass())
            ->pluck('rightable_id')
            ->toArray();
    }

    #[Computed]
    public function declaredAlbumIdsSet(): array
    {
        return $this->declaredRights
            ->where('rightable_type', (new Album)->getMorphClass())
            ->pluck('rightable_id')
            ->toArray();
    }

    #[Computed]
    public function stats(): array
    {
        $userId = Auth::id();
        return [
            'declared_tracks' => ContentRight::where('artist_id', $userId)
                ->where('rightable_type', (new Track)->getMorphClass())
                ->count(),
            'declared_albums' => ContentRight::where('artist_id', $userId)
                ->where('rightable_type', (new Album)->getMorphClass())
                ->count(),
            'total_tracks' => Track::where('artist_id', $userId)->count(),
            'socam_member' => Auth::user()->contentRights()
                ->where('socam_member', true)
                ->exists(),
        ];
    }

    public function toggleTrack(int $trackId): void
    {
        $track = Track::where('artist_id', Auth::id())->findOrFail($trackId);

        $existing = ContentRight::where('artist_id', Auth::id())
            ->where('rightable_type', (new Track)->getMorphClass())
            ->where('rightable_id', $trackId)
            ->first();

        if ($existing) {
            $existing->delete();
            Flux::toast(variant: 'success', text: __('Déclaration retirée pour ":title".', ['title' => $track->title]));
        } else {
            ContentRight::create([
                'artist_id' => Auth::id(),
                'rightable_type' => (new Track)->getMorphClass(),
                'rightable_id' => $trackId,
                'declaration_accepted_at' => now(),
                'socam_member' => $this->socam_member,
                'ip_address' => request()->ip(),
            ]);
            Flux::toast(variant: 'success', text: __('":title" déclaré.', ['title' => $track->title]));
        }
    }

    public function toggleAlbum(int $albumId): void
    {
        $album = Album::where('artist_id', Auth::id())->findOrFail($albumId);

        $existing = ContentRight::where('artist_id', Auth::id())
            ->where('rightable_type', (new Album)->getMorphClass())
            ->where('rightable_id', $albumId)
            ->first();

        if ($existing) {
            $existing->delete();
            Flux::toast(variant: 'success', text: __('Déclaration retirée pour ":title".', ['title' => $album->title]));
        } else {
            ContentRight::create([
                'artist_id' => Auth::id(),
                'rightable_type' => (new Album)->getMorphClass(),
                'rightable_id' => $albumId,
                'declaration_accepted_at' => now(),
                'socam_member' => $this->socam_member,
                'ip_address' => request()->ip(),
            ]);
            Flux::toast(variant: 'success', text: __('":title" déclaré.', ['title' => $album->title]));
        }
    }

    public function updateSocamMembership(): void
    {
        $user = Auth::user();

        // Update all existing rights with new SOCAM status
        ContentRight::where('artist_id', $user->id)
            ->update(['socam_member' => $this->socam_member]);

        Flux::toast(variant: 'success', text: $this->socam_member
            ? __('Vous êtes maintenant membre SOCAM. Vos œuvres sont déclarées auprès de la société de gestion collective.')
            : __('Membre SOCAM désactivé.'));
    }

    public function selectStep(int $step): void
    {
        $this->currentStep = $step;
    }

    public function statusLabel(bool $declared): string
    {
        return $declared ? __('Déclaré') : __('Non déclaré');
    }

    public function statusColor(bool $declared): string
    {
        return $declared
            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
            : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400';
    }
}; ?>

<div class="space-y-8">
    {{-- En-tête --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Droits d\'auteur') }}</flux:heading>
            <flux:subheading>{{ __('Déclarez vos œuvres et gérez vos droits') }}</flux:subheading>
        </div>
    </div>

    {{-- Infobulle SOCAM --}}
    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 dark:border-blue-800 dark:bg-blue-950/30">
        <div class="flex items-start gap-4">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-900/50">
                <flux:icon.information-circle class="size-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-medium text-blue-900 dark:text-blue-200">{{ __('Qu\'est-ce que la déclaration des droits ?') }}</p>
                <p class="mt-1 text-sm text-blue-700 dark:text-blue-300">
                    {{ __('En déclarant vos œuvres via FanPay, vous attestez en être l\'auteur et propriétaire. La SOCAM (Société Camerounaise de l\'Art Musical) est l\'organisme de gestion collective des droits d\'auteur au Cameroun. Si vous êtes membre SOCAM, activez l\'option ci-dessous pour que vos œuvres soient automatiquement identifiées.') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-4">
        <div class="rounded-2xl border border-zinc-200 bg-gradient-to-br from-indigo-50 to-white p-6 dark:border-zinc-700 dark:from-indigo-950/20 dark:to-zinc-900">
            <div class="flex items-center justify-between">
                <flux:icon.musical-note class="size-5 text-indigo-500" />
                <span class="text-xs font-medium text-zinc-400 uppercase tracking-wider">{{ __('Morceaux déclarés') }}</span>
            </div>
            <p class="mt-4 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $this->stats['declared_tracks'] }} / {{ $this->stats['total_tracks'] }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:icon.book-open class="size-5 text-indigo-500" />
                <span class="text-xs font-medium text-zinc-400 uppercase tracking-wider">{{ __('Albums déclarés') }}</span>
            </div>
            <p class="mt-4 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ $this->stats['declared_albums'] }}</p>
        </div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:icon.shield-check class="size-5 text-emerald-500" />
                <span class="text-xs font-medium text-zinc-400 uppercase tracking-wider">{{ __('Membre SOCAM') }}</span>
            </div>
            <p class="mt-4 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">
                {{ $this->stats['socam_member'] ? __('Oui') : __('Non') }}
            </p>
        </div>
    </div>

    {{-- Membre SOCAM --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/30">
                    <flux:icon.shield-check class="size-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <flux:heading>{{ __('Membre SOCAM') }}</flux:heading>
                    <flux:subheading>{{ __('Je suis membre de la Société Camerounaise de l\'Art Musical') }}</flux:subheading>
                </div>
            </div>
            <flux:switch wire:model="socam_member" wire:change="updateSocamMembership" />
        </div>
    </div>

    {{-- Déclaration --}}
    <div class="rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <div class="flex items-center gap-3">
                <flux:heading>{{ __('Déclarez vos œuvres') }}</flux:heading>
                @if ($this->stats['declared_tracks'] > 0 || $this->stats['declared_albums'] > 0)
                    <flux:badge color="emerald">{{ $this->stats['declared_tracks'] + $this->stats['declared_albums'] }} {{ __('déclarée(s)') }}</flux:badge>
                @endif
            </div>
        </div>

        {{-- Tabs --}}
        <div class="border-b border-zinc-200 px-6 dark:border-zinc-700">
            <div class="flex gap-6">
                <button wire:click="selectStep(1)" class="pb-3 text-sm font-medium border-b-2 transition-colors {{ $currentStep === 1 ? 'border-indigo-500 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                    {{ __('Albums') }}
                </button>
                <button wire:click="selectStep(2)" class="pb-3 text-sm font-medium border-b-2 transition-colors {{ $currentStep === 2 ? 'border-indigo-500 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">
                    {{ __('Morceaux') }}
                </button>
            </div>
        </div>

        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @if ($currentStep === 1)
                {{-- Albums --}}
                @forelse ($this->albums as $album)
                    @php $isDeclared = in_array($album->id, $this->declaredAlbumIdsSet); @endphp
                    <div class="flex items-center justify-between px-6 py-4 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                                <flux:icon.book-open class="size-5 text-zinc-500" />
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-sm text-zinc-900 truncate dark:text-white">{{ $album->title }}</p>
                                <p class="text-xs text-zinc-500">{{ $album->tracks_count }} {{ __('morceaux') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $this->statusColor($isDeclared) }}">
                                {{ $this->statusLabel($isDeclared) }}
                            </span>
                            <flux:button
                                variant="{{ $isDeclared ? 'danger' : 'primary' }}"
                                size="sm"
                                wire:click="toggleAlbum({{ $album->id }})"
                            >
                                {{ $isDeclared ? __('Retirer') : __('Déclarer') }}
                            </flux:button>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center">
                        <flux:text>{{ __('Aucun album à déclarer. Créez d\'abord des albums dans votre catalogue.') }}</flux:text>
                    </div>
                @endforelse
            @else
                {{-- Morceaux --}}
                @forelse ($this->tracks as $track)
                    @php $isDeclared = in_array($track->id, $this->declaredTrackIdsSet); @endphp
                    <div class="flex items-center justify-between px-6 py-4 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                                <flux:icon.musical-note class="size-5 text-zinc-500" />
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-sm text-zinc-900 truncate dark:text-white">{{ $track->title }}</p>
                                <p class="text-xs text-zinc-500">{{ $track->album?->title ?? __('Sans album') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $this->statusColor($isDeclared) }}">
                                {{ $this->statusLabel($isDeclared) }}
                            </span>
                            <flux:button
                                variant="{{ $isDeclared ? 'danger' : 'primary' }}"
                                size="sm"
                                wire:click="toggleTrack({{ $track->id }})"
                            >
                                {{ $isDeclared ? __('Retirer') : __('Déclarer') }}
                            </flux:button>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center">
                        <flux:text>{{ __('Aucun morceau à déclarer. Créez d\'abord des morceaux dans votre catalogue.') }}</flux:text>
                    </div>
                @endforelse
            @endif
        </div>
    </div>

    {{ -- Mention légale --}}
    <flux:text class="text-xs text-zinc-400 text-center">
        {{ __('En déclarant vos œuvres, vous confirmez en être l\'auteur et/ou le détenteur des droits. FanPay n\'est pas une société de gestion collective et ne collecte pas les droits d\'auteur pour le compte de la SOCAM. Cette déclaration sert à attester de votre paternité sur les œuvres diffusées via la plateforme.') }}
    </flux:text>
</div>
