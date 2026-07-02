<?php

use App\Domain\Users\Models\User;
use App\Domain\Voting\Models\Candidate;
use App\Domain\Voting\Models\Contest;
use App\Domain\Voting\Models\Vote;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Concours & Votes')] class extends Component {
    public ?int $votingCandidateId = null;
    public int $voteQuantity = 1;

    // Voting form
    public ?int $selectedCandidateId = null;

    #[Computed]
    public function activeContests()
    {
        return Contest::where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->with(['candidates.artist.artistProfile', 'candidates.votes'])
            ->get();
    }

    #[Computed]
    public function finishedContests()
    {
        return Contest::where('status', 'finished')
            ->with(['candidates.artist.artistProfile', 'candidates.votes'])
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();
    }

    public function voteFor(int $candidateId): void
    {
        $this->selectedCandidateId = $candidateId;
        $this->voteQuantity = 1;
    }

    public function submitVote(): void
    {
        if (! Auth::check()) {
            Flux::toast(variant: 'danger', text: __('Vous devez être connecté pour voter.'));
            return;
        }

        $candidate = Candidate::with('contest')->findOrFail($this->selectedCandidateId);
        $contest = $candidate->contest;

        if (! $contest->isActive()) {
            Flux::toast(variant: 'danger', text: __('Ce concours n\'est plus actif.'));
            return;
        }

        $quantity = max(1, min(100, $this->voteQuantity));
        $amount = $quantity * $contest->vote_price;

        Vote::create([
            'candidate_id' => $candidate->id,
            'user_id' => Auth::id(),
            'quantity' => $quantity,
            'amount' => $amount,
            'ip_address' => request()->ip(),
        ]);

        $candidate->increment('vote_count', $quantity);

        $this->selectedCandidateId = null;
        $this->voteQuantity = 1;

        Flux::toast(variant: 'success', text: __('Votre vote a été enregistré ! :quantity vote(s) pour :candidate', [
            'quantity' => $quantity,
            'candidate' => $candidate->artist?->artistProfile?->stage_name ?? $candidate->artist?->name ?? '',
        ]));
    }

    public function cancelVote(): void
    {
        $this->selectedCandidateId = null;
    }


}; ?>

<div class="max-w-5xl mx-auto space-y-12">
    {{-- Header --}}
    <div class="text-center">
        <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-amber-100 dark:bg-amber-900/30">
            <flux:icon.trophy class="size-8 text-amber-500" />
        </div>
        <flux:heading size="xl">{{ __('Concours & Votes') }}</flux:heading>
        <flux:subheading>{{ __('Votez pour vos artistes préférés et suivez les classements') }}</flux:subheading>
    </div>

    {{-- Concours actifs --}}
    @forelse ($this->activeContests as $contest)
        <section>
            <div class="mb-6 text-center">
                <flux:heading size="lg">{{ $contest->title }}</flux:heading>
                @if ($contest->description)
                    <flux:subheading class="max-w-2xl mx-auto mt-2">{{ $contest->description }}</flux:subheading>
                @endif
                <div class="mt-3 flex items-center justify-center gap-4 text-sm text-zinc-500">
                    <span class="inline-flex items-center gap-1">
                        <flux:icon.currency-dollar class="size-4" />
                        {{ number_format($contest->vote_price) }} FCFA / {{ __('vote') }}
                    </span>
                    @if ($contest->ends_at)
                        <span class="inline-flex items-center gap-1">
                            <flux:icon.clock class="size-4" />
                            {{ __('Fin le :date', ['date' => $contest->ends_at->translatedFormat('d M Y H:i')]) }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Candidats --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($contest->candidates->sortByDesc('vote_count') as $candidate)
                    <div class="group relative rounded-2xl border border-zinc-200 bg-white p-6 transition-all hover:shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                        {{-- Position badge --}}
                        @php $rank = $loop->index + 1; @endphp
                        <div class="absolute -top-2 -left-2 flex size-8 items-center justify-center rounded-full text-sm font-bold text-white shadow-md
                            {{ $rank === 1 ? 'bg-amber-500' : ($rank === 2 ? 'bg-zinc-400' : ($rank === 3 ? 'bg-amber-700' : 'bg-zinc-300 dark:bg-zinc-600')) }}">
                            {{ $rank }}
                        </div>

                        <div class="text-center">
                            <div class="mx-auto mb-3 flex size-16 items-center justify-center rounded-2xl bg-zinc-100 text-2xl font-bold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                {{ $candidate->artist?->initials() ?? '?' }}
                            </div>
                            <h3 class="font-semibold text-zinc-900 dark:text-white">
                                {{ $candidate->artist?->artistProfile?->stage_name ?? $candidate->artist?->name ?? __('Artiste') }}
                            </h3>
                            <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-white">
                                {{ $candidate->vote_count }}
                                <span class="text-sm font-normal text-zinc-500">{{ __('votes') }}</span>
                            </p>

                            {{-- Barre de progression --}}
                            @php $maxVotes = $contest->candidates->max('vote_count'); @endphp
                            @if ($maxVotes > 0)
                                <div class="mt-3 h-2 w-full rounded-full bg-zinc-100 dark:bg-zinc-800">
                                    <div class="h-2 rounded-full bg-gradient-to-r from-amber-400 to-amber-600 transition-all duration-500"
                                         style="width: {{ ($candidate->vote_count / $maxVotes) * 100 }}%">
                                    </div>
                                </div>
                            @endif

                            {{-- Voter --}}
                            @if ($selectedCandidateId === $candidate->id)
                                <div class="mt-4 space-y-3 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                                    <flux:input wire:model="voteQuantity" type="number" min="1" max="100" :label="__('Nombre de votes')" value="1" />
                                    <div class="text-sm font-medium text-zinc-900 dark:text-white">
                                        {{ __('Total') }} : {{ number_format($voteQuantity * $contest->vote_price) }} FCFA
                                    </div>
                                    <div class="flex gap-2">
                                        <flux:button variant="primary" wire:click="submitVote" class="flex-1">
                                            <flux:icon.heart class="size-4" />
                                            {{ __('Voter') }}
                                        </flux:button>
                                        <flux:button variant="ghost" wire:click="cancelVote">{{ __('Annuler') }}</flux:button>
                                    </div>
                                </div>
                            @else
                                <div class="mt-4">
                                    <flux:button variant="primary" wire:click="voteFor({{ $candidate->id }})" class="w-full">
                                        <flux:icon.heart class="size-4" />
                                        {{ __('Voter') }}
                                    </flux:button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <div class="p-16 text-center">
            <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                <flux:icon.trophy class="size-8 text-zinc-400" />
            </div>
            <p class="font-medium text-zinc-900 dark:text-white">{{ __('Aucun concours actif pour le moment') }}</p>
            <flux:text class="mt-1">{{ __('Revenez bientôt pour voter pour vos artistes préférés !') }}</flux:text>
        </div>
    @endforelse

    {{-- Concours terminés --}}
    @if ($this->finishedContests->isNotEmpty())
        <section>
            <flux:heading size="lg" class="text-center mb-6">{{ __('Concours terminés') }}</flux:heading>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->finishedContests as $contest)
                    <div class="rounded-2xl border border-zinc-200 bg-white p-6 opacity-75 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:heading size="sm">{{ $contest->title }}</flux:heading>
                        <div class="mt-3 space-y-1">
                            @foreach ($contest->candidates->sortByDesc('vote_count')->take(3) as $candidate)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="flex items-center gap-2">
                                        <span class="text-zinc-400">{{ $loop->index + 1 }}.</span>
                                        {{ $candidate->artist?->artistProfile?->stage_name ?? $candidate->artist?->name ?? '?' }}
                                    </span>
                                    <span class="font-medium">{{ $candidate->vote_count }} {{ __('votes') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
