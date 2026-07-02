<?php

use App\Domain\Users\Models\User;
use App\Domain\Voting\Models\Candidate;
use App\Domain\Voting\Models\Contest;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Concours')] class extends Component {
    public bool $showForm = false;
    public ?int $editingContestId = null;

    public string $form_title = '';
    public string $form_description = '';
    public string $form_vote_price = '';
    public string $form_starts_at = '';
    public string $form_ends_at = '';
    public string $form_status = 'draft';

    // Candidate management
    public bool $showCandidateForm = false;
    public ?int $managingContestId = null;
    public string $candidate_search = '';
    public ?int $selectedArtistId = null;

    protected function rules(): array
    {
        return [
            'form_title' => ['required', 'string', 'max:255'],
            'form_description' => ['nullable', 'string', 'max:5000'],
            'form_vote_price' => ['required', 'integer', 'min:0', 'max:100000'],
            'form_starts_at' => ['nullable', 'date'],
            'form_ends_at' => ['nullable', 'date', 'after:form_starts_at'],
            'form_status' => ['required', 'in:draft,active,finished,cancelled'],
        ];
    }

    #[Computed]
    public function contests()
    {
        return Contest::withCount('candidates')
            ->orderByRaw("FIELD(status, 'active', 'draft', 'finished', 'cancelled')")
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function managingContest()
    {
        if (! $this->managingContestId) return null;
        return Contest::with(['candidates.artist.artistProfile'])->find($this->managingContestId);
    }

    #[Computed]
    public function searchArtists()
    {
        if (strlen($this->candidate_search) < 2) return collect();
        return User::where('role', 'artist')
            ->where(function ($q) {
                $q->where('name', 'like', '%'.$this->candidate_search.'%');
            })
            ->limit(10)
            ->get();
    }

    public function createContest(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->editingContestId = null;
    }

    public function editContest(int $id): void
    {
        $contest = Contest::findOrFail($id);
        $this->form_title = $contest->title;
        $this->form_description = $contest->description ?? '';
        $this->form_vote_price = (string) $contest->vote_price;
        $this->form_starts_at = $contest->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->form_ends_at = $contest->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->form_status = $contest->status;
        $this->editingContestId = $contest->id;
        $this->showForm = true;
    }

    public function saveContest(): void
    {
        $data = $this->validate();

        $attributes = [
            'title' => $data['form_title'],
            'description' => $data['form_description'],
            'vote_price' => (int) $this->form_vote_price,
            'starts_at' => $this->form_starts_at ?: null,
            'ends_at' => $this->form_ends_at ?: null,
            'status' => $data['form_status'],
        ];

        if ($this->editingContestId) {
            Contest::findOrFail($this->editingContestId)->update($attributes);
            Flux::toast(variant: 'success', text: __('Concours mis à jour.'));
        } else {
            Contest::create($attributes);
            Flux::toast(variant: 'success', text: __('Concours créé avec succès.'));
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function manageCandidates(int $contestId): void
    {
        $this->managingContestId = $contestId;
        $this->showCandidateForm = false;
        $this->candidate_search = '';
    }

    public function addCandidate(): void
    {
        if (! $this->managingContestId || ! $this->selectedArtistId) return;

        $exists = Candidate::where('contest_id', $this->managingContestId)
            ->where('artist_id', $this->selectedArtistId)
            ->exists();

        if ($exists) {
            Flux::toast(variant: 'danger', text: __('Cet artiste est déjà candidat.'));
            return;
        }

        Candidate::create([
            'contest_id' => $this->managingContestId,
            'artist_id' => $this->selectedArtistId,
        ]);

        $this->selectedArtistId = null;
        $this->candidate_search = '';
        $this->showCandidateForm = false;
        Flux::toast(variant: 'success', text: __('Artiste ajouté au concours.'));
    }

    public function removeCandidate(int $candidateId): void
    {
        Candidate::where('contest_id', $this->managingContestId)
            ->where('id', $candidateId)
            ->delete();
        Flux::toast(variant: 'success', text: __('Candidat retiré.'));
    }

    public function closeManageCandidates(): void
    {
        $this->managingContestId = null;
        $this->showCandidateForm = false;
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'form_title', 'form_description', 'form_vote_price',
            'form_starts_at', 'form_ends_at', 'form_status',
            'editingContestId',
        ]);
        $this->form_status = 'draft';
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            'draft' => __('Brouillon'),
            'active' => __('Actif'),
            'finished' => __('Terminé'),
            'cancelled' => __('Annulé'),
            default => $status,
        };
    }

    public function statusColor(string $status): string
    {
        return match ($status) {
            'draft' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-400',
            'active' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
            'finished' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-400',
        };
    }
}; ?>

<div class="space-y-8">
    {{-- En-tête --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Concours & Votes') }}</flux:heading>
            <flux:subheading>{{ __('Créez et gérez les concours de vote') }}</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="createContest">
            <flux:icon.plus class="size-4" />
            {{ __('Nouveau concours') }}
        </flux:button>
    </div>

    {{-- Formulaire --}}
    @if ($showForm)
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <form wire:submit="saveContest" class="space-y-5 max-w-2xl">
                <flux:input wire:model="form_title" :label="__('Titre du concours')" required placeholder="{{ __('ex: Meilleur Artiste de l\'Année 2026') }}" />

                <flux:textarea wire:model="form_description" :label="__('Description')" rows="3" placeholder="{{ __('Description et règles du concours...') }}" />

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <flux:input wire:model="form_vote_price" :label="__('Prix du vote (FCFA)')" type="number" min="0" step="50" required placeholder="100" />

                    <flux:input wire:model="form_starts_at" :label="__('Début')" type="datetime-local" />
                    <flux:input wire:model="form_ends_at" :label="__('Fin')" type="datetime-local" />
                </div>

                <flux:select wire:model="form_status" :label="__('Statut')">
                    <option value="draft">{{ __('Brouillon') }}</option>
                    <option value="active">{{ __('Actif') }}</option>
                    <option value="finished">{{ __('Terminé') }}</option>
                    <option value="cancelled">{{ __('Annulé') }}</option>
                </flux:select>

                <div class="flex items-center gap-3 pt-2">
                    <flux:button variant="primary" type="submit">
                        {{ $editingContestId ? __('Enregistrer') : __('Créer le concours') }}
                    </flux:button>
                    <flux:button variant="ghost" wire:click="cancelForm">{{ __('Annuler') }}</flux:button>
                </div>
            </form>
        </div>
    @endif

    {{-- Gestion des candidats --}}
    @if ($managingContestId && $this->managingContest)
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <flux:button variant="ghost" wire:click="closeManageCandidates" icon="arrow-left" size="sm" />
                    <flux:heading size="sm">{{ $this->managingContest->title }}</flux:heading>
                    <flux:badge :color="$this->statusColor($this->managingContest->status)">{{ $this->statusLabel($this->managingContest->status) }}</flux:badge>
                </div>
                <flux:button variant="primary" size="sm" wire:click="$set('showCandidateForm', true)">
                    <flux:icon.plus class="size-4" />
                    {{ __('Ajouter un artiste') }}
                </flux:button>
            </div>

            {{-- Ajout candidat --}}
            @if ($showCandidateForm)
                <div class="mb-4 rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                    <flux:input wire:model.live="candidate_search" placeholder="{{ __('Rechercher un artiste...') }}" icon="magnifying-glass" />
                    @if ($this->searchArtists->isNotEmpty())
                        <div class="mt-2 divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach ($this->searchArtists as $artist)
                                <button wire:click="$set('selectedArtistId', {{ $artist->id }})" class="flex w-full items-center gap-3 px-2 py-2 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800 {{ $selectedArtistId === $artist->id ? 'bg-zinc-100 dark:bg-zinc-800 font-medium' : '' }}">
                                    <div class="flex size-8 items-center justify-center rounded-lg bg-zinc-200 text-xs font-bold text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                        {{ $artist->initials() }}
                                    </div>
                                    {{ $artist->name }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                    @if ($selectedArtistId)
                        <div class="mt-3 flex items-center gap-2">
                            <flux:button size="sm" variant="primary" wire:click="addCandidate">{{ __('Ajouter') }}</flux:button>
                            <flux:button size="sm" variant="ghost" wire:click="$set('selectedArtistId', null)">{{ __('Annuler') }}</flux:button>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Liste candidats --}}
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->managingContest->candidates as $candidate)
                    <div class="flex items-center justify-between py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex size-10 items-center justify-center rounded-xl bg-zinc-100 text-sm font-bold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                {{ $candidate->artist?->initials() ?? '?' }}
                            </div>
                            <div>
                                <p class="font-medium text-sm text-zinc-900 dark:text-white">
                                    {{ $candidate->artist?->artistProfile?->stage_name ?? $candidate->artist?->name ?? __('Artiste inconnu') }}
                                </p>
                                <p class="text-xs text-zinc-500">{{ $candidate->vote_count }} {{ __('votes') }}</p>
                            </div>
                        </div>
                        <flux:button variant="ghost" size="sm" icon="trash" wire:click="removeCandidate({{ $candidate->id }})" wire:confirm="{{ __('Retirer ce candidat ?') }}" class="text-red-500" />
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-zinc-500">{{ __('Aucun candidat. Ajoutez des artistes au concours.') }}</p>
                @endforelse
            </div>
        </div>
    @endif

    {{-- Liste des concours --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->contests as $contest)
            <div class="rounded-2xl border border-zinc-200 bg-white transition-all hover:shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-3">
                        <flux:heading size="sm" class="!leading-tight">{{ $contest->title }}</flux:heading>
                        <span class="shrink-0 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $this->statusColor($contest->status) }}">
                            {{ $this->statusLabel($contest->status) }}
                        </span>
                    </div>

                    @if ($contest->description)
                        <p class="text-sm text-zinc-500 line-clamp-2 mb-3">{{ $contest->description }}</p>
                    @endif

                    <div class="flex flex-wrap gap-3 text-xs text-zinc-500 mb-4">
                        <span class="inline-flex items-center gap-1">
                            <flux:icon.users class="size-3.5" /> {{ $contest->candidates_count }} {{ __('candidats') }}
                        </span>
                        <span class="inline-flex items-center gap-1">
                            <flux:icon.currency-dollar class="size-3.5" /> {{ number_format($contest->vote_price) }} FCFA/{{ __('vote') }}
                        </span>
                        @if ($contest->starts_at)
                            <span class="inline-flex items-center gap-1">
                                <flux:icon.calendar class="size-3.5" /> {{ $contest->starts_at->translatedFormat('d M') }} - {{ $contest->ends_at?->translatedFormat('d M Y') ?? '...' }}
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                        <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="editContest({{ $contest->id }})" :title="__('Modifier')" />
                        <flux:button variant="ghost" size="sm" icon="user-plus" wire:click="manageCandidates({{ $contest->id }})" :title="__('Gérer les candidats')" />
                        <flux:spacer />
                        <flux:badge size="sm" color="{{ $contest->status === 'active' ? 'emerald' : 'zinc' }}">
                            {{ $contest->status === 'active' ? __('En cours') : ucfirst($contest->status) }}
                        </flux:badge>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full p-16 text-center">
                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon.trophy class="size-8 text-zinc-400" />
                </div>
                <p class="font-medium text-zinc-900 dark:text-white">{{ __('Aucun concours') }}</p>
                <flux:text class="mt-1">{{ __('Créez votre premier concours de vote.') }}</flux:text>
                <flux:button variant="primary" wire:click="createContest" class="mt-4">
                    {{ __('Créer un concours') }}
                </flux:button>
            </div>
        @endforelse
    </div>
</div>
