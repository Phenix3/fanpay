@php $title = __('messages.supervote_title') . ' - ' . config('app.name'); @endphp

<x-layouts::public>
    <!-- Atmospheric Background -->
    <div class="fixed inset-0 z-[-1] bg-radial-gradient pointer-events-none"></div>

    <div class="flex-grow px-margin-mobile md:px-margin-desktop pb-32 md:pb-24 max-w-container-max mx-auto w-full">

        {{-- Hero Section --}}
        <section class="relative mt-24 md:mt-32 mb-12 md:mb-16">
            <div class="flex flex-col items-center text-center gap-6">
                {{-- Trophy Icon --}}
                <div class="w-16 h-16 md:w-20 md:h-20 rounded-2xl bg-gradient-to-br from-amber-400/20 to-amber-600/10 border border-amber-500/20 flex items-center justify-center shadow-[0_0_30px_rgba(245,158,11,0.15)]">
                    <span class="material-symbols-outlined text-4xl md:text-5xl text-amber-400"
                          style="font-variation-settings: 'FILL' 1;">emoji_events</span>
                </div>

                <div>
                    <h1 class="font-display-lg text-display-lg text-on-background tracking-tight">
                        SuperVote
                        <span class="text-amber-400 neon-text-secondary">.</span>
                    </h1>
                    <p class="font-body-lg text-body-lg text-on-surface-variant max-w-xl mt-3">
                        {{ __('messages.supervote_description') }}
                    </p>
                </div>
            </div>
        </section>

        {{-- Active Contest --}}
        @if($activeContest)
            <section class="mb-16 md:mb-20">
                {{-- Contest Header --}}
                <div class="glass-panel rounded-2xl p-6 md:p-8 mb-8 text-center relative overflow-hidden">
                    {{-- Background decoration --}}
                    <div class="absolute top-0 right-0 w-64 h-64 bg-amber-500/5 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="absolute bottom-0 left-0 w-48 h-48 bg-primary-fixed/5 rounded-full blur-3xl pointer-events-none"></div>

                    @if($activeContest->cover)
                        <div class="w-full h-32 md:h-48 rounded-xl overflow-hidden mb-6 relative">
                            <div class="absolute inset-0 bg-cover bg-center"
                                 style="background-image: url('{{ $activeContest->cover }}');">
                            </div>
                            <div class="absolute inset-0 bg-gradient-to-t from-background/60 to-transparent"></div>
                        </div>
                    @endif

                    <h2 class="font-display-md text-display-md text-on-surface mb-3">
                        {{ $activeContest->title }}
                    </h2>

                    @if($activeContest->description)
                        <p class="font-body-md text-body-md text-on-surface-variant max-w-2xl mx-auto mb-4">
                            {{ $activeContest->description }}
                        </p>
                    @endif

                    <div class="flex flex-wrap items-center justify-center gap-4 md:gap-6 text-sm">
                        <div class="flex items-center gap-2 font-label-md text-label-md text-on-surface-variant">
                            <span class="material-symbols-outlined text-amber-400 text-lg">currency_franc</span>
                            {{ number_format($activeContest->vote_price, 0, ',', ' ') }} FCFA / {{ __('messages.supervote_per_vote') }}
                        </div>
                        @if($activeContest->ends_at)
                            <div class="flex items-center gap-2 font-label-md text-label-md text-on-surface-variant" x-data="timer('{{ $activeContest->ends_at->toIso8601String() }}')" x-init="init()">
                                <span class="material-symbols-outlined text-amber-400 text-lg">schedule</span>
                                <span x-text="display">{{ $activeContest->ends_at->diffForHumans() }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Candidates Grid --}}
                @php $sortedCandidates = $activeContest->candidates->sortByDesc('vote_count'); @endphp
                @php $maxVotes = $sortedCandidates->max('vote_count') ?? 1; @endphp

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 md:gap-6">
                    @foreach($sortedCandidates as $candidate)
                        @php $rank = $loop->index + 1; @endphp
                        @php $artistName = $candidate->artist?->artistProfile?->stage_name ?? $candidate->artist?->name ?? __('messages.artist'); @endphp

                        <div class="glass-panel rounded-2xl p-6 flex flex-col items-center text-center group hover:bg-surface-container-high transition-all duration-300 glow-border relative @if($rank === 1) ring-1 ring-amber-500/30 shadow-[0_0_30px_rgba(245,158,11,0.1)] @endif">
                            {{-- Rank Badge --}}
                            <div class="absolute -top-3 -left-3 w-9 h-9 rounded-full flex items-center justify-center font-label-md text-label-md font-bold shadow-lg z-10
                                @if($rank === 1) bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-amber-500/30
                                @elseif($rank === 2) bg-gradient-to-br from-zinc-300 to-zinc-400 text-white
                                @elseif($rank === 3) bg-gradient-to-br from-amber-700 to-amber-800 text-white
                                @else bg-surface-variant text-on-surface-variant @endif">
                                {{ $rank }}
                            </div>

                            {{-- Avatar --}}
                            <div class="w-16 h-16 md:w-20 md:h-20 rounded-full overflow-hidden mb-4 bg-surface-container-high ring-2
                                @if($rank === 1) ring-amber-500/40 @else ring-outline-variant/30 @endif
                                group-hover:ring-primary-fixed-dim/50 transition-all duration-300">
                                @if($candidate->artist?->artistProfile?->avatar)
                                    <img src="{{ $candidate->artist->artistProfile->avatar }}"
                                         alt="{{ $artistName }}"
                                         class="w-full h-full object-cover" />
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-surface-container-high">
                                        <span class="material-symbols-outlined text-outline text-3xl">person</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Name --}}
                            <h3 class="font-headline-md text-headline-md text-on-surface group-hover:text-primary-fixed transition-colors">
                                {{ $artistName }}
                            </h3>

                            {{-- Vote Count --}}
                            <p class="font-display-lg text-display-lg text-on-background mt-2">
                                {{ number_format($candidate->vote_count, 0, ',', ' ') }}
                                <span class="font-label-md text-label-md text-on-surface-variant">{{ __('messages.supervote_votes') }}</span>
                            </p>

                            {{-- Progress Bar --}}
                            <div class="w-full mt-4 h-2 rounded-full bg-surface-container-high overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-amber-400 to-amber-500 transition-all duration-700"
                                     style="width: {{ ($candidate->vote_count / $maxVotes) * 100 }}%;">
                                </div>
                            </div>

                            {{-- Vote CTA --}}
                            <div class="mt-5 w-full">
                                @auth
                                    <a href="{{ route('contests') }}"
                                       class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-label-md text-label-md font-bold bg-amber-500 text-black hover:bg-amber-400 active:scale-[0.98] transition-all shadow-[0_0_20px_rgba(245,158,11,0.3)]">
                                        <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">favorite</span>
                                        {{ __('messages.supervote_vote_now') }}
                                    </a>
                                @else
                                    <a href="{{ route('login') }}"
                                       class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-label-md text-label-md font-bold glass-panel text-on-surface-variant hover:text-primary-fixed hover:bg-surface-container-high border border-outline-variant/30 active:scale-[0.98] transition-all">
                                        <span class="material-symbols-outlined text-sm">login</span>
                                        {{ __('messages.supervote_login_to_vote') }}
                                    </a>
                                @endauth
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @else
            {{-- No Active Contest --}}
            <section class="mb-16">
                <div class="glass-panel rounded-2xl p-12 md:p-16 text-center max-w-lg mx-auto">
                    <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4"
                          style="font-variation-settings: 'FILL' 1;">emoji_events</span>
                    <h2 class="font-headline-md text-headline-md text-on-surface mb-2">
                        {{ __('messages.supervote_no_active') }}
                    </h2>
                    <p class="font-body-md text-body-md text-on-surface-variant">
                        {{ __('messages.supervote_no_active_desc') }}
                    </p>
                </div>
            </section>
        @endif

        {{-- Finished Contests --}}
        @if($finishedContests->isNotEmpty())
            <section>
                <div class="flex items-center gap-3 mb-8">
                    <span class="material-symbols-outlined text-on-surface-variant text-xl">history</span>
                    <h2 class="font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-surface">
                        {{ __('messages.supervote_past_contests') }}
                    </h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 md:gap-6">
                    @foreach($finishedContests as $contest)
                        <div class="glass-panel rounded-2xl p-6 opacity-70 hover:opacity-90 transition-opacity">
                            <h3 class="font-headline-md text-headline-md text-on-surface mb-4">
                                {{ $contest->title }}
                            </h3>

                            <div class="space-y-2">
                                @foreach($contest->candidates->sortByDesc('vote_count')->take(3) as $candidate)
                                    @php $artistName = $candidate->artist?->artistProfile?->stage_name ?? $candidate->artist?->name ?? __('messages.artist'); @endphp
                                    <div class="flex items-center justify-between font-body-md text-body-md">
                                        <span class="flex items-center gap-2 text-on-surface-variant">
                                            <span class="font-label-md text-label-md
                                                @if($loop->index === 0) text-amber-400
                                                @elseif($loop->index === 1) text-zinc-400
                                                @elseif($loop->index === 2) text-amber-700
                                                @endif">
                                                {{ $loop->index + 1 }}.
                                            </span>
                                            {{ $artistName }}
                                        </span>
                                        <span class="font-label-md text-label-md text-on-surface">
                                            {{ number_format($candidate->vote_count, 0, ',', ' ') }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>

                            @if($contest->ends_at)
                                <p class="font-body-md text-body-md text-on-surface-variant text-xs mt-4">
                                    {{ __('messages.supervote_ended_on') }} {{ $contest->ends_at->isoFormat('D MMMM YYYY') }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts::public>

<script>
    // Alpine.js timer component for countdown
    document.addEventListener('alpine:init', () => {
        Alpine.data('timer', (deadline) => ({
            display: '',
            interval: null,
            init() {
                this.update();
                this.interval = setInterval(() => this.update(), 1000);
            },
            destroy() {
                if (this.interval) clearInterval(this.interval);
            },
            update() {
                const now = new Date().getTime();
                const end = new Date(deadline).getTime();
                const diff = end - now;

                if (diff <= 0) {
                    this.display = '{{ __('messages.supervote_ended') }}';
                    if (this.interval) clearInterval(this.interval);
                    return;
                }

                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                let parts = [];
                if (days > 0) parts.push(days + 'd');
                parts.push(String(hours).padStart(2, '0') + 'h');
                parts.push(String(minutes).padStart(2, '0') + 'm');
                parts.push(String(seconds).padStart(2, '0') + 's');
                this.display = parts.join(' ');
            }
        }));
    });
</script>
