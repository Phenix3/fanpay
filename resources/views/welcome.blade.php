@php $title = __('messages.home'); @endphp

<x-layouts::public>
    <!-- Ambient Background Glows -->
    <div class="ambient-glow top-0 left-[-200px]"></div>
    <div class="ambient-glow-secondary top-[40%] right-[-100px]"></div>

    <!-- Hero Section -->
    <section
        class="relative rounded-[2rem] md:rounded-[3rem] overflow-hidden min-h-[716px] flex items-center shadow-[0_24px_48px_rgba(0,0,0,0.8)] mx-margin-mobile md:mx-margin-desktop mt-24 md:mt-32"
    >
        <!-- Background Image -->
        <div class="absolute inset-0 z-0">
            <div
                class="w-full h-full bg-cover bg-center"
                style="background-image: url('https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=1400&q=80');"
            ></div>
            <!-- Gradient Overlay -->
            <div
                class="absolute inset-0 bg-gradient-to-r from-background via-background/80 to-transparent"
            ></div>
            <div
                class="absolute inset-0 bg-gradient-to-t from-background via-transparent to-transparent"
            ></div>
        </div>
        <div
            class="relative z-10 w-full md:w-2/3 p-8 md:p-16 flex flex-col gap-6"
        >
            <div
                class="inline-flex items-center gap-2 bg-surface-container/50 backdrop-blur-md border border-outline-variant/30 rounded-full px-4 py-2 w-max"
            >
                <span
                    class="w-2 h-2 rounded-full bg-secondary-container animate-pulse"
                ></span>
                <span
                    class="font-label-md text-label-md text-on-surface"
                >{{ __('messages.new_release') }}</span>
            </div>
            <h1 class="font-display-lg text-display-lg text-primary">
                {{ __('messages.hero_headline') }}<br />
                <span class="text-gradient-electric">{{ __('messages.hero_headline_highlight') }}</span>
            </h1>
            <p
                class="font-body-lg text-body-lg text-on-surface-variant max-w-xl"
            >
                {{ __('messages.hero_subtitle') }}
            </p>
            <div class="flex flex-wrap gap-4 mt-4">
                <button type="button"
                    class="bg-primary-fixed text-on-primary-fixed font-label-md text-label-md px-8 py-4 rounded-xl active:scale-95 transition-transform flex items-center gap-2 shadow-[0_0_20px_rgba(86,255,168,0.3)] hover:bg-primary-container"
                >
                    <span
                        class="material-symbols-outlined"
                        style="font-variation-settings: 'FILL' 1;"
                    >play_arrow</span>
                    {{ __('messages.listen_now') }}
                </button>
                <button type="button"
                    class="glass-panel text-primary-fixed font-label-md text-label-md px-8 py-4 rounded-xl active:scale-95 transition-transform hover:border-primary-fixed hover:bg-white/5 flex items-center gap-2"
                >
                    <span class="material-symbols-outlined">explore</span>
                    {{ __('messages.discover') }}
                </button>
            </div>
        </div>
    </section>

    <!-- Trending Now (Bento Grid) -->
    <section class="space-y-8 relative px-margin-mobile md:px-margin-desktop mt-24 max-w-container-max mx-auto">
        <div class="flex justify-between items-end">
            <div>
                <h2
                    class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-primary"
                >
                    {{ __('messages.trending_now') }}
                </h2>
                <p
                    class="font-body-md text-body-md text-on-surface-variant mt-2"
                >
                    {{ __('messages.trending_subtitle') }}
                </p>
            </div>
            <button type="button"
                class="text-primary-fixed font-label-md text-label-md hover:text-primary-container hidden md:flex items-center gap-1"
            >
                {{ __('messages.see_all') }}
                <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            <!-- Large Featured Track -->
            <div
                class="md:col-span-8 glass-panel rounded-2xl p-6 relative group overflow-hidden flex flex-col justify-end min-h-[320px] active:scale-[0.98] transition-transform cursor-pointer"
            >
                <div class="absolute inset-0 z-0">
                    <div
                        class="w-full h-full bg-cover bg-center transition-transform duration-700 group-hover:scale-105"
                        style="background-image: url('https://images.unsplash.com/photo-1571330735066-03aaa9429d89?w=800&q=80');"
                    ></div>
                    <div
                        class="absolute inset-0 bg-gradient-to-t from-background/90 via-background/40 to-transparent"
                    ></div>
                </div>
                <div
                    class="relative z-10 flex justify-between items-end w-full"
                >
                    <div>
                        <span
                            class="inline-block px-3 py-1 bg-surface-container-low/80 backdrop-blur-sm text-primary-fixed font-label-md text-label-md rounded-full mb-3 border border-primary-fixed/30"
                        >{{ __('messages.afrobeats') }}</span>
                        <h3
                            class="font-headline-md text-headline-md text-primary mb-1"
                        >
                            {{ __('messages.featured_track') }}
                        </h3>
                        <p
                            class="font-body-md text-body-md text-on-surface-variant"
                        >
                            {{ __('messages.featured_artist') }}
                        </p>
                    </div>
                    <button type="button"
                        class="w-14 h-14 rounded-full bg-primary-fixed text-on-primary-fixed flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 shadow-[0_0_15px_rgba(86,255,168,0.5)]"
                    >
                        <span
                            class="material-symbols-outlined"
                            style="font-variation-settings: 'FILL' 1;"
                        >play_arrow</span>
                    </button>
                </div>
            </div>
            <!-- Smaller Tracks -->
            <div class="md:col-span-4 flex flex-col gap-6">
                <div
                    class="glass-panel rounded-2xl p-4 flex gap-4 items-center group cursor-pointer hover:bg-white/[0.08] transition-colors"
                >
                    <div
                        class="w-20 h-20 rounded-xl overflow-hidden flex-shrink-0 relative"
                    >
                        <img
                            alt="{{ __('messages.track_cover') }}"
                            class="w-full h-full object-cover"
                            src="https://images.unsplash.com/photo-1511379938547-c1f69419868d?w=200&q=80"
                        />
                        <div
                            class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                        >
                            <span
                                class="material-symbols-outlined text-secondary-container"
                            >play_arrow</span>
                        </div>
                    </div>
                    <div>
                        <h4
                            class="font-label-md text-label-md text-primary mb-1 line-clamp-1"
                        >
                            {{ __('messages.track_1') }}
                        </h4>
                        <p
                            class="font-body-md text-body-md text-on-surface-variant text-sm"
                        >
                            {{ __('messages.track_1_artist') }}
                        </p>
                    </div>
                </div>
                <div
                    class="glass-panel rounded-2xl p-4 flex gap-4 items-center group cursor-pointer hover:bg-white/[0.08] transition-colors"
                >
                    <div
                        class="w-20 h-20 rounded-xl overflow-hidden flex-shrink-0 relative"
                    >
                        <img
                            alt="{{ __('messages.track_cover') }}"
                            class="w-full h-full object-cover"
                            src="https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=200&q=80"
                        />
                        <div
                            class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                        >
                            <span
                                class="material-symbols-outlined text-secondary-container"
                            >play_arrow</span>
                        </div>
                    </div>
                    <div>
                        <h4
                            class="font-label-md text-label-md text-primary mb-1 line-clamp-1"
                        >
                            {{ __('messages.track_2') }}
                        </h4>
                        <p
                            class="font-body-md text-body-md text-on-surface-variant text-sm"
                        >
                            {{ __('messages.track_2_artist') }}
                        </p>
                    </div>
                </div>
                <div
                    class="glass-panel rounded-2xl p-4 flex gap-4 items-center group cursor-pointer hover:bg-white/[0.08] transition-colors"
                >
                    <div
                        class="w-20 h-20 rounded-xl overflow-hidden flex-shrink-0 relative"
                    >
                        <img
                            alt="{{ __('messages.track_cover') }}"
                            class="w-full h-full object-cover"
                            src="https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=200&q=80"
                        />
                        <div
                            class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                        >
                            <span
                                class="material-symbols-outlined text-secondary-container"
                            >play_arrow</span>
                        </div>
                    </div>
                    <div>
                        <h4
                            class="font-label-md text-label-md text-primary mb-1 line-clamp-1"
                        >
                            {{ __('messages.track_3') }}
                        </h4>
                        <p
                            class="font-body-md text-body-md text-on-surface-variant text-sm"
                        >
                            {{ __('messages.track_3_artist') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SuperVote CTA Section -->
    <section
        class="relative rounded-[2rem] overflow-hidden bg-surface-container-high border border-outline-variant/30 p-8 md:p-16 text-center md:text-left flex flex-col md:flex-row items-center gap-12 shadow-2xl mx-margin-mobile md:mx-margin-desktop mt-24 mb-24"
    >
        <!-- Decorative Glow inside CTA -->
        <div
            class="absolute top-0 right-0 w-64 h-64 bg-primary-fixed/20 blur-[100px] rounded-full pointer-events-none"
        ></div>
        <div class="flex-1 z-10 relative">
            <span
                class="inline-flex items-center gap-2 text-secondary-container font-label-md text-label-md mb-4 uppercase tracking-wider"
            >
                <span
                    class="material-symbols-outlined text-lg"
                    style="font-variation-settings: 'FILL' 1;"
                >how_to_vote</span>
                {{ __('messages.annual_event') }}
            </span>
            <h2
                class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-primary mb-6"
            >
                {{ __('messages.supervote_title') }}<br />
                <span class="text-gradient-electric">{{ __('messages.supervote_highlight') }}</span>
            </h2>
            <p
                class="font-body-lg text-body-lg text-on-surface-variant mb-8 max-w-lg"
            >
                {{ __('messages.supervote_description') }}
            </p>
            <button type="button"
                class="bg-gradient-electric text-on-primary-fixed font-label-md text-label-md px-8 py-4 rounded-xl active:scale-95 transition-all shadow-[0_8px_24px_rgba(0,227,139,0.3)] hover:shadow-[0_12px_32px_rgba(0,227,139,0.5)] flex items-center justify-center gap-2 w-full md:w-auto"
            >
                {{ __('messages.vote_now') }}
                <span class="material-symbols-outlined">arrow_forward</span>
            </button>
        </div>
        <div class="flex-1 w-full relative z-10">
            <!-- Stacked Glass Cards for Nominees -->
            <div class="relative h-[300px] w-full max-w-md mx-auto">
                <!-- Back Card -->
                <div
                    class="absolute top-0 right-4 w-4/5 h-4/5 glass-panel rounded-2xl transform rotate-6 scale-90 opacity-60"
                >
                    <div
                        class="w-full h-full bg-cover bg-center rounded-2xl"
                        style="background-image: url('https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=400&q=80');"
                    ></div>
                </div>
                <!-- Middle Card -->
                <div
                    class="absolute top-4 left-4 w-4/5 h-4/5 glass-panel rounded-2xl transform -rotate-3 scale-95 opacity-80"
                >
                    <div
                        class="w-full h-full bg-cover bg-center rounded-2xl"
                        style="background-image: url('https://images.unsplash.com/photo-1571266028243-e4733b0f0bb0?w=400&q=80');"
                    ></div>
                </div>
                <!-- Front Card (Active) -->
                <div
                    class="absolute bottom-0 left-1/2 -translate-x-1/2 w-4/5 h-[85%] glass-active rounded-2xl p-4 flex flex-col justify-end"
                >
                    <div
                        class="absolute inset-0 z-0 rounded-2xl overflow-hidden"
                    >
                        <div
                            class="w-full h-full bg-cover bg-center"
                            style="background-image: url('https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=400&q=80');"
                        ></div>
                        <div
                            class="absolute inset-0 bg-gradient-to-t from-background/90 to-transparent"
                        ></div>
                    </div>
                    <div class="relative z-10">
                        <h4
                            class="font-headline-md text-headline-md text-primary"
                        >
                            {{ __('messages.nominee_name') }}
                        </h4>
                        <p
                            class="font-label-md text-label-md text-primary-fixed"
                        >
                            {{ __('messages.nominee_label') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts::public>
