@php $title = __('messages.explorer_artists') . ' - ' . config('app.name'); @endphp

<x-layouts::public>
    <!-- Atmospheric Background -->
    <div class="fixed inset-0 z-[-1] bg-radial-gradient pointer-events-none"></div>

    <div class="flex-grow px-margin-mobile md:px-margin-desktop pb-32 md:pb-24 max-w-container-max mx-auto w-full">

        {{-- Header --}}
        <header class="flex flex-col gap-6 md:gap-8 items-start mt-24 md:mt-32 mb-8 md:mb-12">
            <div class="flex flex-col gap-2">
                <h1 class="font-display-lg text-display-lg text-on-background tracking-tight">
                    {{ __('messages.explorer_artists') }}
                    <span class="text-primary-fixed neon-text-primary">.</span>
                </h1>
                <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl">
                    {{ __('messages.explorer_artists_subtitle') }}
                </p>
            </div>

            {{-- Search Bar --}}
            <form method="GET" action="{{ route('artists.index') }}" class="w-full max-w-md relative group glow-focus rounded-full transition-all duration-300">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant group-focus-within:text-primary-fixed transition-colors">search</span>
                <input
                    class="glass-panel font-body-md text-body-md text-on-background pl-12 pr-12 py-3 rounded-full w-full focus:outline-none focus:ring-0 placeholder-on-surface-variant/50 transition-all duration-300 bg-transparent border-outline-variant/30"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="{{ __('messages.search_artists_placeholder') }}"
                    type="text"
                />
                @if(request('search'))
                    <a href="{{ route('artists.index') }}" class="absolute right-4 top-1/2 -translate-y-1/2 text-on-surface-variant hover:text-primary-fixed transition-colors">
                        <span class="material-symbols-outlined">close</span>
                    </a>
                @endif
            </form>
        </header>

        {{-- Results Count --}}
        <div class="flex items-center justify-between mb-8">
            <p class="font-body-md text-body-md text-on-surface-variant">
                {{ __('messages.artists_count', ['count' => $artists->total()]) }}
            </p>
        </div>

        {{-- Artist Grid --}}
        @if($artists->count() > 0)
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                @foreach($artists as $artist)
                    @php $profile = $artist->artistProfile; @endphp
                    <a href="{{ route('artists.show', $profile->slug) }}"
                       class="glass-panel rounded-2xl p-4 md:p-6 flex flex-col items-center text-center group hover:bg-surface-container-high transition-all duration-300 glow-border"
                       wire:navigate>
                        {{-- Avatar --}}
                        <div class="w-20 h-20 md:w-24 md:h-24 rounded-full overflow-hidden mb-4 bg-surface-container-high ring-2 ring-outline-variant/30 group-hover:ring-primary-fixed-dim/50 transition-all duration-300">
                            @if($profile->avatar)
                                <img src="{{ $profile->avatar }}" alt="{{ $profile->stage_name ?: $artist->name }}" class="w-full h-full object-cover" />
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-surface-container-high">
                                    <span class="material-symbols-outlined text-outline text-3xl">person</span>
                                </div>
                            @endif
                        </div>

                        {{-- Name --}}
                        <h3 class="font-headline-md text-headline-md text-on-surface group-hover:text-primary-fixed transition-colors truncate max-w-full">
                            {{ $profile->stage_name ?: $artist->name }}
                        </h3>

                        {{-- Genre + Verified --}}
                        <div class="flex items-center gap-2 mt-1">
                            @if($profile->is_verified)
                                <span class="material-symbols-outlined text-primary-fixed text-[16px]"
                                      style="font-variation-settings: 'FILL' 1;">verified</span>
                            @endif
                            @if($profile->genre)
                                <span class="font-label-md text-label-md text-on-surface-variant">{{ $profile->genre->name }}</span>
                            @endif
                        </div>

                        {{-- Bio snippet --}}
                        @php
                            $bio = app()->getLocale() === 'en' && $profile->bio_en ? $profile->bio_en : $profile->bio;
                        @endphp
                        @if($bio)
                            <p class="font-body-md text-body-md text-on-surface-variant text-sm mt-3 line-clamp-2">
                                {{ $bio }}
                            </p>
                        @endif

                        {{-- CTA --}}
                        <span class="mt-auto pt-4 text-primary-fixed font-label-md text-label-md flex items-center gap-1 group-hover:gap-2 transition-all">
                            {{ __('messages.view_profile') }}
                            <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                        </span>
                    </a>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-12 flex justify-center">
                {{ $artists->onEachSide(1)->links() }}
            </div>
        @else
            {{-- Empty State --}}
            <div class="glass-panel rounded-2xl p-12 md:p-16 text-center max-w-lg mx-auto">
                <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4">mic_off</span>
                <h2 class="font-headline-md text-headline-md text-on-surface mb-2">
                    {{ __('messages.no_artists_found') }}
                </h2>
                <p class="font-body-md text-body-md text-on-surface-variant mb-6">
                    {{ __('messages.no_artists_found_desc') }}
                </p>
                <a href="{{ route('artists.index') }}"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-primary-fixed-dim text-on-primary rounded-xl font-label-md text-label-md font-bold hover:brightness-110 transition-all">
                    {{ __('messages.clear_search') }}
                </a>
            </div>
        @endif
    </div>
</x-layouts::public>
