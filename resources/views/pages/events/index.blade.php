@php $title = __('messages.events_title') . ' - ' . config('app.name'); @endphp

<x-layouts::public>
    <!-- Atmospheric Background -->
    <div class="fixed inset-0 z-[-1] bg-radial-gradient pointer-events-none"></div>

    <div class="flex-grow px-margin-mobile md:px-margin-desktop pb-32 md:pb-24 max-w-container-max mx-auto w-full">

        {{-- Header --}}
        <header class="flex flex-col gap-6 md:gap-8 items-start mt-24 md:mt-32 mb-8 md:mb-12">
            <div class="flex flex-col gap-2">
                <h1 class="font-display-lg text-display-lg text-on-background tracking-tight">
                    {{ __('messages.events_title') }}
                    <span class="text-primary-fixed neon-text-primary">.</span>
                </h1>
                <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl">
                    {{ __('messages.events_subtitle') }}
                </p>
            </div>

            {{-- Filters Row --}}
            <div class="flex flex-col md:flex-row gap-4 w-full md:items-center md:justify-between">
                {{-- Period Tabs --}}
                <div class="flex gap-2">
                    <a href="{{ route('events.index', array_merge(request()->except('period', 'page'), ['period' => 'upcoming'])) }}"
                       class="px-5 py-2 rounded-full font-label-md text-label-md transition-all duration-300 active:scale-95
                              @if($currentPeriod === 'upcoming')
                                  bg-primary-fixed text-on-primary-fixed shadow-[0_0_15px_rgba(86,255,168,0.3)]
                              @else
                                  glass-panel text-on-surface-variant hover:text-primary-fixed hover:bg-surface-container-high
                              @endif">
                        {{ __('messages.events_upcoming') }}
                    </a>
                    <a href="{{ route('events.index', array_merge(request()->except('period', 'page'), ['period' => 'past'])) }}"
                       class="px-5 py-2 rounded-full font-label-md text-label-md transition-all duration-300 active:scale-95
                              @if($currentPeriod === 'past')
                                  bg-primary-fixed text-on-primary-fixed shadow-[0_0_15px_rgba(86,255,168,0.3)]
                              @else
                                  glass-panel text-on-surface-variant hover:text-primary-fixed hover:bg-surface-container-high
                              @endif">
                        {{ __('messages.events_past') }}
                    </a>
                </div>

                {{-- Search --}}
                <form method="GET" action="{{ route('events.index') }}" class="relative group glow-focus rounded-full transition-all duration-300 w-full max-w-sm">
                    <input type="hidden" name="period" value="{{ $currentPeriod }}">
                    <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant group-focus-within:text-primary-fixed transition-colors">search</span>
                    <input
                        class="glass-panel font-body-md text-body-md text-on-background pl-12 pr-12 py-3 rounded-full w-full focus:outline-none focus:ring-0 placeholder-on-surface-variant/50 transition-all duration-300 bg-transparent border-outline-variant/30"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="{{ __('messages.events_search_placeholder') }}"
                        type="text"
                    />
                    @if(request('search'))
                        <a href="{{ route('events.index', request()->except('search', 'page')) }}" class="absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-primary-fixed transition-colors">
                            <span class="material-symbols-outlined">close</span>
                        </a>
                    @endif
                </form>
            </div>
        </header>

        {{-- Results Count --}}
        <div class="flex items-center justify-between mb-8">
            <p class="font-body-md text-body-md text-on-surface-variant">
                {{ __('messages.events_count', ['count' => $events->total()]) }}
            </p>
        </div>

        {{-- Events Grid --}}
        @if($events->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 md:gap-6">
                @foreach($events as $event)
                    @php
                        $isUpcoming = $event->date && $event->date->isFuture();
                        $isToday = $event->date && $event->date->isToday();
                        $minPrice = $event->ticketTypes ? $event->ticketTypes->min('price') : null;
                        $artistName = $event->artist?->artistProfile?->stage_name ?? $event->artist?->name ?? '';
                    @endphp
                    <a href="{{ route('events.show', $event->slug) }}"
                       class="glass-panel rounded-2xl overflow-hidden group hover:bg-surface-container-high transition-all duration-300 glow-border flex flex-col"
                       wire:navigate>
                        {{-- Cover --}}
                        <div class="relative h-48 md:h-56 overflow-hidden shrink-0">
                            <div class="absolute inset-0 bg-cover bg-center transition-transform duration-700 group-hover:scale-105"
                                 style="background-image: url('{{ $event->cover ?? 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=600&q=80' }}');">
                            </div>
                            <div class="absolute inset-0 bg-gradient-to-t from-background/60 to-transparent z-10"></div>

                            {{-- Badge --}}
                            <div class="absolute top-3 left-3 z-20">
                                @if($isToday)
                                    <span class="inline-block px-3 py-1 rounded-full bg-primary-container text-on-primary-container font-label-md text-label-md uppercase tracking-wider text-xs shadow-[0_0_12px_rgba(0,255,157,0.4)]">
                                        {{ __('messages.event_today') }}
                                    </span>
                                @elseif($isUpcoming)
                                    <span class="inline-block px-3 py-1 rounded-full bg-secondary-container text-on-secondary-container font-label-md text-label-md uppercase tracking-wider text-xs shadow-[0_0_12px_rgba(255,86,44,0.3)]">
                                        {{ __('messages.event_upcoming') }}
                                    </span>
                                @else
                                    <span class="inline-block px-3 py-1 rounded-full bg-surface-variant text-on-surface-variant font-label-md text-label-md uppercase tracking-wider text-xs">
                                        {{ __('messages.event_past') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Content --}}
                        <div class="p-4 md:p-5 flex flex-col gap-3 flex-grow">
                            <h3 class="font-headline-md text-headline-md text-on-surface group-hover:text-primary-fixed transition-colors line-clamp-1">
                                {{ $event->title }}
                            </h3>

                            <div class="space-y-1.5 font-body-md text-body-md text-on-surface-variant">
                                @if($artistName)
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-primary-fixed-dim text-[16px]">mic</span>
                                        <span class="text-sm truncate">{{ $artistName }}</span>
                                    </div>
                                @endif
                                @if($event->date)
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-primary-fixed-dim text-[16px]">calendar_month</span>
                                        <span class="text-sm">{{ $event->date->isoFormat('D MMM YYYY') }} à {{ $event->date->format('H:i') }}</span>
                                    </div>
                                @endif
                                @if($event->venue)
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-primary-fixed-dim text-[16px]">location_on</span>
                                        <span class="text-sm truncate">{{ $event->venue }}</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Footer: Price / CTA --}}
                            <div class="mt-auto pt-3 flex items-center justify-between border-t border-outline-variant/20">
                                <div>
                                    @if($minPrice !== null)
                                        <span class="font-headline-md text-headline-md text-primary-fixed">
                                            {{ number_format($minPrice, 0, ',', ' ') }}
                                        </span>
                                        <span class="font-label-md text-label-md text-on-surface-variant text-xs"> FCFA</span>
                                    @else
                                        <span class="font-label-md text-label-md text-on-surface-variant">{{ __('messages.events_free') }}</span>
                                    @endif
                                </div>
                                <span class="font-label-md text-label-md text-primary-fixed flex items-center gap-1 group-hover:gap-2 transition-all">
                                    {{ __('messages.events_get_tickets') }}
                                    <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-12 flex justify-center">
                {{ $events->onEachSide(1)->appends(request()->query())->links() }}
            </div>
        @else
            {{-- Empty State --}}
            <div class="glass-panel rounded-2xl p-12 md:p-16 text-center max-w-lg mx-auto">
                <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4">event_busy</span>
                <h2 class="font-headline-md text-headline-md text-on-surface mb-2">
                    {{ $currentPeriod === 'upcoming' ? __('messages.events_no_upcoming') : __('messages.events_no_past') }}
                </h2>
                <p class="font-body-md text-body-md text-on-surface-variant mb-6">
                    {{ __('messages.events_no_results_desc') }}
                </p>
                <a href="{{ route('events.index') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-primary-fixed-dim text-on-primary rounded-xl font-label-md text-label-md font-bold hover:brightness-110 transition-all">
                    {{ __('messages.events_clear_filters') }}
                </a>
            </div>
        @endif
    </div>
</x-layouts::public>
