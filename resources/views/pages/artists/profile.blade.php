@php $title = $profile->stage_name ?: $artist->name; @endphp

<x-layouts::public>
    <!-- Hero Section -->
    <section
        class="relative w-full h-[614px] md:h-[716px] flex items-end pb-margin-desktop px-margin-mobile md:px-margin-desktop"
    >
        <div class="absolute inset-0 z-0">
            <div
                class="w-full h-full bg-cover bg-center"
                style="background-image: url('{{ $profile->cover ?? 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=1200' }}');"
            ></div>
            <!-- Gradient Overlay for readability -->
            <div
                class="absolute inset-0 bg-gradient-to-t from-background via-background/80 to-transparent"
            ></div>
        </div>
        <div
            class="relative z-10 w-full max-w-container-max mx-auto flex flex-col md:flex-row justify-between items-end gap-6"
        >
            <div>
                <div class="flex items-center gap-3 mb-4">
                    @if($profile->is_verified)
                        <span
                            class="px-3 py-1 rounded-full glass-panel font-label-md text-label-md text-primary-fixed flex items-center gap-1 border-primary-fixed/30"
                        >
                            <span
                                class="material-symbols-outlined text-[16px]"
                                style="font-variation-settings: 'FILL' 1;"
                            >verified</span>
                            {{ __('messages.verified_artist') }}
                        </span>
                    @endif
                    @if($profile->genre)
                        <span
                            class="px-3 py-1 rounded-full bg-surface-container-highest/80 backdrop-blur-md font-label-md text-label-md text-on-surface"
                        >{{ $profile->genre->name }}</span>
                    @endif
                </div>
                <h1
                    class="font-display-lg text-display-lg text-white mb-2 text-glow"
                >
                    {{ $profile->stage_name ?: $artist->name }}
                </h1>
                <p
                    class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl"
                >
                    {{ app()->getLocale() === 'en' && $profile->bio_en ? $profile->bio_en : $profile->bio }}
                </p>
            </div>
            <div class="flex gap-4 w-full md:w-auto">                        <button type="button"
                    class="flex-1 md:flex-none px-8 py-4 bg-primary-fixed text-on-primary-fixed rounded-xl font-label-md text-label-md hover:bg-primary-fixed-dim active:scale-95 transition-all flex items-center justify-center gap-2 shadow-[0_0_24px_rgba(0,227,139,0.3)]"
                >
                    <span
                        class="material-symbols-outlined"
                        style="font-variation-settings: 'FILL' 1;"
                    >play_arrow</span>
                    {{ __('messages.play_radio') }}
                </button>
                <button type="button"
                    class="flex-1 md:flex-none px-8 py-4 glass-panel text-primary-fixed rounded-xl font-label-md text-label-md hover:border-primary-fixed active:scale-95 transition-all flex items-center justify-center gap-2"
                >
                    <span class="material-symbols-outlined">person_add</span>
                    {{ __('messages.follow') }}
                </button>
            </div>
        </div>
    </section>

    <!-- Stats Strip -->
    <section
        class="w-full border-y border-outline-variant/10 bg-surface-container-lowest/50 py-6 px-margin-mobile md:px-margin-desktop backdrop-blur-sm"
    >
        <div
            class="max-w-container-max mx-auto flex justify-between md:justify-start md:gap-24"
        >
            <div>
                <div
                    class="font-label-md text-label-md text-on-surface-variant mb-1 uppercase tracking-wider"
                >{{ __('messages.monthly_listeners') }}</div>
                <div
                    class="font-headline-md text-headline-md text-on-surface"
                >{{ $stats['monthly_listeners'] ?? '0' }}</div>
            </div>
            <div>
                <div
                    class="font-label-md text-label-md text-on-surface-variant mb-1 uppercase tracking-wider"
                >{{ __('messages.followers') }}</div>
                <div
                    class="font-headline-md text-headline-md text-on-surface"
                >{{ $stats['followers'] ?? '0' }}</div>
            </div>
            <div class="hidden md:block">
                <div
                    class="font-label-md text-label-md text-on-surface-variant mb-1 uppercase tracking-wider"
                >{{ __('messages.global_rank') }}</div>
                <div
                    class="font-headline-md text-headline-md text-primary-fixed"
                >#{{ $stats['global_rank'] ?? '-' }}</div>
            </div>
        </div>
    </section>

    <!-- Albums / Discography Section -->
    @if($albums->isNotEmpty())
    <section class="w-full py-16 px-margin-mobile md:px-margin-desktop">
        <div class="max-w-container-max mx-auto">
            <h2 class="font-headline-lg text-headline-lg text-on-surface mb-10">
                {{ __('messages.discography') }}
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @foreach($albums as $album)
                <div class="group cursor-pointer neon-glow-hover rounded-xl p-4 transition-all duration-300">
                    <div class="aspect-square rounded-xl overflow-hidden mb-4 bg-surface-container-high relative">
                        @if($album->cover)
                            <img src="{{ $album->cover }}" alt="{{ $album->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <span class="material-symbols-outlined text-outline text-5xl">music_note</span>
                            </div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-background/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end justify-end p-3">
                            <span class="material-symbols-outlined text-primary-fixed text-3xl drop-shadow-lg">play_circle</span>
                        </div>
                    </div>
                    <h3 class="font-headline-md text-headline-md text-on-surface group-hover:text-primary-fixed transition-colors truncate">
                        {{ $album->title }}
                    </h3>
                    <p class="font-body-md text-body-md text-on-surface-variant mt-1">
                        {{ $album->tracks_count }} {{ __('messages.tracks_count') }}
                    </p>
                    @if($album->price > 0)
                    <p class="font-label-md text-label-md text-primary-fixed mt-1">
                        {{ number_format($album->price) }} FCFA
                    </p>
                    @else
                    <p class="font-label-md text-label-md text-tertiary-container mt-1">
                        {{ __('messages.free') }}
                    </p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- Upcoming Events Section -->
    @if($events->isNotEmpty())
    <section class="w-full py-16 px-margin-mobile md:px-margin-desktop bg-surface-container-low/30">
        <div class="max-w-container-max mx-auto">
            <h2 class="font-headline-lg text-headline-lg text-on-surface mb-10">
                {{ __('messages.upcoming_events') }}
            </h2>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($events as $event)
                <div class="glass-panel rounded-xl p-6 neon-glow-hover transition-all duration-300">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-16 h-16 rounded-xl bg-primary-fixed/10 flex flex-col items-center justify-center text-center">
                            <span class="font-label-md text-label-md text-primary-fixed uppercase">{{ $event->date->format('M') }}</span>
                            <span class="font-headline-md text-headline-md text-on-surface leading-none">{{ $event->date->format('d') }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-headline-md text-headline-md text-on-surface truncate">{{ $event->title }}</h3>
                            <p class="font-body-md text-body-md text-on-surface-variant mt-1 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[16px]">location_on</span>
                                {{ $event->venue }}
                            </p>
                            <div class="flex items-center justify-between mt-3">
                                <span class="font-body-md text-body-md text-on-surface-variant">
                                    {{ $event->ticket_types_count ?? 0 }} {{ __('messages.ticket_types') }}
                                </span>
                                <button type="button" class="px-4 py-2 bg-primary-fixed-dim text-on-primary rounded-lg font-label-md text-label-md hover:brightness-110 active:scale-95 transition-all">
                                    {{ __('messages.tickets') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- VIP Plans Section -->
    @if($vipPlans->isNotEmpty())
    <section class="w-full py-16 px-margin-mobile md:px-margin-desktop">
        <div class="max-w-container-max mx-auto">
            <h2 class="font-headline-lg text-headline-lg text-on-surface mb-10">
                {{ __('messages.vip_membership') }}
            </h2>
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($vipPlans as $plan)
                <div class="glass-panel rounded-xl p-6 neon-glow-hover transition-all duration-300 flex flex-col">
                    <div class="flex items-center justify-between mb-4">
                        <span class="font-headline-md text-headline-md text-on-surface">{{ $plan->name }}</span>
                        <span class="font-label-md text-label-md text-primary-fixed">{{ number_format($plan->price) }} FCFA</span>
                    </div>
                    @if($plan->perks)
                    <ul class="space-y-2 mb-6 flex-1">
                        @foreach($plan->perks as $perk)
                        <li class="flex items-start gap-2 font-body-md text-body-md text-on-surface-variant">
                            <span class="material-symbols-outlined text-primary-fixed text-[18px] flex-shrink-0" style="font-variation-settings: 'FILL' 1;">check_circle</span>
                            {{ $perk }}
                        </li>
                        @endforeach
                    </ul>
                    @endif
                    <div class="flex items-center justify-between mt-auto pt-4 border-t border-outline-variant/20">
                        <span class="font-body-md text-body-md text-on-surface-variant">
                            {{ $plan->subscriptions_count }} {{ __('messages.subscribers') }}
                        </span>
                        <button type="button" class="px-5 py-2.5 bg-primary-fixed text-on-primary-fixed rounded-xl font-label-md text-label-md hover:bg-primary-fixed-dim active:scale-95 transition-all">
                                    {{ __('messages.subscribe') }}
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif
</x-layouts::public>
