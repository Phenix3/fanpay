@php $title = __('messages.boutique'); @endphp

<x-layouts::public>
    <!-- Atmospheric Background -->
    <div class="fixed inset-0 z-[-1] bg-radial-gradient pointer-events-none"></div>

    <!-- Desktop Search Bar (inside main content, below nav) -->
    <div class="hidden md:flex items-center justify-between px-margin-desktop pt-24 pb-4 max-w-container-max mx-auto w-full">
        <div class="relative group glow-focus rounded-full transition-all duration-300 w-full max-w-md">
            <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant group-focus-within:text-primary-fixed transition-colors">search</span>
            <input
                class="glass-panel font-body-md text-body-md text-on-background pl-12 pr-4 py-3 rounded-full w-full focus:outline-none focus:ring-0 placeholder-on-surface-variant/50 transition-all duration-300 bg-transparent border-outline-variant/30"
                placeholder="{{ __('messages.search_placeholder') }}"
                type="text"
            />
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-grow px-margin-mobile md:px-margin-desktop pb-32 md:pb-24 max-w-container-max mx-auto w-full flex flex-col gap-12 md:gap-16">
        <!-- Header Section -->
        <header class="flex flex-col gap-6 md:gap-8 items-start">
            <div class="flex flex-col gap-2">
                <h1 class="font-display-lg text-display-lg text-on-background tracking-tight">
                    {{ __('messages.boutique_title_part1') }}
                    <span class="text-primary-fixed neon-text-primary">{{ __('messages.boutique_title_part2') }}</span>
                </h1>
                <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl">
                    {{ __('messages.boutique_subtitle') }}
                </p>
            </div>
            <!-- Mobile Search -->
            <div class="w-full md:hidden relative group glow-focus rounded-full transition-all duration-300 mt-4">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant group-focus-within:text-primary-fixed transition-colors">search</span>
                <input
                    class="glass-panel font-body-md text-body-md text-on-background pl-12 pr-4 py-3 rounded-full w-full focus:outline-none focus:ring-0 placeholder-on-surface-variant/50 transition-all duration-300 bg-transparent border-outline-variant/30"
                    placeholder="{{ __('messages.search') }}"
                    type="text"
                />
            </div>
            <!-- Category Filters -->
            <div class="flex gap-4 overflow-x-auto pb-4 w-full scrollbar-hide snap-x">
                <button type="button" class="snap-start whitespace-nowrap px-6 py-2 rounded-full bg-primary-fixed text-on-primary-fixed font-label-md text-label-md transition-transform hover:scale-105 active:scale-95 shadow-[0_0_15px_rgba(86,255,168,0.3)]">
                    {{ __('messages.all_items') }}
                </button>
                <button type="button" class="snap-start whitespace-nowrap px-6 py-2 rounded-full glass-panel text-on-surface hover:text-primary-fixed font-label-md text-label-md transition-all hover:bg-surface-container-high active:scale-95">
                    {{ __('messages.albums_vinyl') }}
                </button>
                <button type="button" class="snap-start whitespace-nowrap px-6 py-2 rounded-full glass-panel text-on-surface hover:text-primary-fixed font-label-md text-label-md transition-all hover:bg-surface-container-high active:scale-95">
                    {{ __('messages.concert_tickets') }}
                </button>
                <button type="button" class="snap-start whitespace-nowrap px-6 py-2 rounded-full glass-panel text-on-surface hover:text-primary-fixed font-label-md text-label-md transition-all hover:bg-surface-container-high active:scale-95">
                    {{ __('messages.merch') }}
                </button>
            </div>
        </header>

        <!-- Featured / Trending Grid (Bento Style) -->
        <section class="grid grid-cols-1 md:grid-cols-12 gap-6 auto-rows-[300px]">
            <!-- Hero Item (Spans 2 rows, 8 cols on desktop) -->
            <article class="md:col-span-8 md:row-span-2 glass-panel rounded-3xl overflow-hidden relative group cursor-pointer glow-border transition-all duration-500">
                <div class="absolute inset-0 bg-gradient-to-t from-background via-background/50 to-transparent z-10"></div>
                <div class="absolute inset-0 bg-cover bg-center transition-transform duration-700 group-hover:scale-105"
                     style="background-image: url('https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=1200&q=80');"></div>
                <div class="absolute inset-0 z-20 p-8 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <span class="bg-secondary-container text-on-secondary-container px-3 py-1 rounded-full font-label-md text-label-md uppercase tracking-wider backdrop-blur-md bg-opacity-90 shadow-[0_0_10px_rgba(255,86,44,0.5)]">
                            {{ __('messages.hot_ticket') }}
                        </span>
                        <button type="button" class="w-12 h-12 rounded-full glass-panel flex items-center justify-center hover:bg-primary-fixed hover:text-on-primary-fixed transition-colors text-on-surface">
                            <span class="material-symbols-outlined">favorite</span>
                        </button>
                    </div>
                    <div class="flex flex-col gap-4 max-w-xl">
                        <div>
                            <h2 class="font-headline-lg text-headline-lg-mobile md:text-headline-lg text-primary-fixed neon-text-primary mb-2">
                                {{ __('messages.featured_event_title') }}
                            </h2>
                            <p class="font-body-lg text-body-lg text-on-surface-variant line-clamp-2">
                                {{ __('messages.featured_event_desc') }}
                            </p>
                        </div>
                        <div class="flex items-end justify-between mt-4">
                            <div class="flex flex-col">
                                <span class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">{{ __('messages.starting_at') }}</span>
                                <span class="font-display-lg text-display-lg text-on-background">
                                    45,000<span class="text-headline-md text-primary-fixed"> FCFA</span>
                                </span>
                            </div>
                            <button type="button" class="bg-primary-fixed text-on-primary-fixed px-8 py-4 rounded-xl font-label-md text-label-md hover:bg-primary-fixed-dim transition-all shadow-[0_0_20px_rgba(86,255,168,0.4)] active:scale-95 flex items-center gap-2">
                                <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">confirmation_number</span>
                                {{ __('messages.get_tickets') }}
                            </button>
                        </div>
                    </div>
                </div>
            </article>

            <!-- Secondary Item 1 (Spans 1 row, 4 cols) -->
            <article class="md:col-span-4 glass-panel rounded-3xl overflow-hidden relative group cursor-pointer glow-border transition-all duration-500 flex flex-col">
                <div class="h-48 relative overflow-hidden shrink-0">
                    <div class="absolute inset-0 bg-gradient-to-t from-background to-transparent z-10"></div>
                    <div class="absolute inset-0 bg-cover bg-center transition-transform duration-700 group-hover:scale-110"
                         style="background-image: url('https://images.unsplash.com/photo-1511379938547-c1f69419868d?w=600&q=80');"></div>
                    <span class="absolute top-4 left-4 z-20 bg-tertiary-container text-on-tertiary-container px-3 py-1 rounded-full font-label-md text-label-md uppercase tracking-wider shadow-[0_0_10px_rgba(255,221,86,0.5)]">
                        {{ __('messages.new_release') }}
                    </span>
                </div>
                <div class="p-6 flex-grow flex flex-col justify-between z-20 bg-surface-container-low/80 backdrop-blur-md">
                    <div>
                        <h3 class="font-headline-md text-headline-md text-on-background mb-1 group-hover:text-primary-fixed transition-colors">
                            {{ __('messages.featured_product_1') }}
                        </h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">{{ __('messages.featured_product_1_desc') }}</p>
                    </div>
                    <div class="flex items-center justify-between mt-4">
                        <span class="font-headline-md text-headline-md text-primary-fixed">15,000 FCFA</span>
                        <button type="button" class="w-10 h-10 rounded-full border border-primary-fixed text-primary-fixed flex items-center justify-center hover:bg-primary-fixed hover:text-on-primary-fixed transition-all">
                            <span class="material-symbols-outlined">shopping_cart</span>
                        </button>
                    </div>
                </div>
            </article>

            <!-- Secondary Item 2 (Spans 1 row, 4 cols, Sold Out) -->
            <article class="md:col-span-4 glass-panel rounded-3xl overflow-hidden relative group cursor-pointer transition-all duration-500 flex flex-col opacity-75 grayscale-[30%] hover:grayscale-0 hover:opacity-100">
                <div class="h-48 relative overflow-hidden shrink-0">
                    <div class="absolute inset-0 bg-gradient-to-t from-background to-transparent z-10"></div>
                    <div class="absolute inset-0 bg-cover bg-center transition-transform duration-700 group-hover:scale-110"
                         style="background-image: url('https://images.unsplash.com/photo-1523398002811-999ca8dec234?w=600&q=80');"></div>
                    <span class="absolute top-4 left-4 z-20 bg-error text-on-error px-3 py-1 rounded-full font-label-md text-label-md uppercase tracking-wider shadow-[0_0_10px_rgba(255,180,171,0.5)]">
                        {{ __('messages.sold_out') }}
                    </span>
                </div>
                <div class="p-6 flex-grow flex flex-col justify-between z-20 bg-surface-container-low/80 backdrop-blur-md">
                    <div>
                        <h3 class="font-headline-md text-headline-md text-on-background mb-1">{{ __('messages.featured_product_2') }}</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant">{{ __('messages.featured_product_2_desc') }}</p>
                    </div>
                    <div class="flex items-center justify-between mt-4">
                        <span class="font-headline-md text-headline-md text-on-surface-variant line-through">35,000 FCFA</span>
                        <button type="button" class="px-4 py-2 rounded-lg border border-outline-variant text-on-surface-variant font-label-md text-label-md cursor-not-allowed">
                            {{ __('messages.waitlist') }}
                        </button>
                    </div>
                </div>
            </article>
        </section>

        <!-- Catalog Grid -->
        <section class="flex flex-col gap-8">
            <div class="flex justify-between items-end border-b border-outline-variant/20 pb-4">
                <h2 class="font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-background">
                    {{ __('messages.latest_drops') }}
                </h2>
                <a class="font-label-md text-label-md text-primary-fixed hover:text-primary-fixed-dim transition-colors flex items-center gap-1" href="#">
                    {{ __('messages.view_all') }}
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                <!-- Catalog Item 1 -->
                <article class="glass-panel rounded-2xl p-4 flex flex-col gap-4 group hover:bg-surface-container-high transition-colors glow-border">
                    <div class="aspect-square rounded-xl overflow-hidden relative">
                        <div class="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover:scale-105"
                             style="background-image: url('https://images.unsplash.com/photo-1614613535308-eb5fbd3d2c17?w=400&q=80');"></div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <h3 class="font-label-md text-label-md text-on-background truncate group-hover:text-primary-fixed transition-colors">{{ __('messages.catalog_item_1') }}</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant text-sm truncate">{{ __('messages.catalog_item_1_artist') }}</p>
                    </div>
                    <div class="flex justify-between items-center mt-auto">
                        <span class="font-headline-md text-headline-md text-on-background text-lg">5,000 FCFA</span>
                        <button type="button" class="w-8 h-8 rounded-full bg-surface-container border border-outline-variant text-on-surface flex items-center justify-center group-hover:border-primary-fixed group-hover:text-primary-fixed transition-colors">
                            <span class="material-symbols-outlined text-[18px]">add</span>
                        </button>
                    </div>
                </article>
                <!-- Catalog Item 2 -->
                <article class="glass-panel rounded-2xl p-4 flex flex-col gap-4 group hover:bg-surface-container-high transition-colors glow-border">
                    <div class="aspect-square rounded-xl overflow-hidden relative">
                        <div class="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover:scale-105"
                             style="background-image: url('https://images.unsplash.com/photo-1501281668745-f7f57925c3b4?w=400&q=80');"></div>
                        <span class="absolute top-2 left-2 bg-secondary-container text-on-secondary-container text-xs px-2 py-0.5 rounded-full font-label-md uppercase tracking-wider">{{ __('messages.fast_track') }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <h3 class="font-label-md text-label-md text-on-background truncate group-hover:text-primary-fixed transition-colors">{{ __('messages.catalog_item_2') }}</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant text-sm truncate">{{ __('messages.catalog_item_2_artist') }}</p>
                    </div>
                    <div class="flex justify-between items-center mt-auto">
                        <span class="font-headline-md text-headline-md text-on-background text-lg">25,000 FCFA</span>
                        <button type="button" class="w-8 h-8 rounded-full bg-surface-container border border-outline-variant text-on-surface flex items-center justify-center group-hover:border-primary-fixed group-hover:text-primary-fixed transition-colors">
                            <span class="material-symbols-outlined text-[18px]">add</span>
                        </button>
                    </div>
                </article>
                <!-- Catalog Item 3 -->
                <article class="glass-panel rounded-2xl p-4 flex flex-col gap-4 group hover:bg-surface-container-high transition-colors glow-border">
                    <div class="aspect-square rounded-xl overflow-hidden relative">
                        <div class="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover:scale-105"
                             style="background-image: url('https://images.unsplash.com/photo-1576566588028-4147f3842f27?w=400&q=80');"></div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <h3 class="font-label-md text-label-md text-on-background truncate group-hover:text-primary-fixed transition-colors">{{ __('messages.catalog_item_3') }}</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant text-sm truncate">{{ __('messages.catalog_item_3_artist') }}</p>
                    </div>
                    <div class="flex justify-between items-center mt-auto">
                        <span class="font-headline-md text-headline-md text-on-background text-lg">12,500 FCFA</span>
                        <button type="button" class="w-8 h-8 rounded-full bg-surface-container border border-outline-variant text-on-surface flex items-center justify-center group-hover:border-primary-fixed group-hover:text-primary-fixed transition-colors">
                            <span class="material-symbols-outlined text-[18px]">add</span>
                        </button>
                    </div>
                </article>
                <!-- Catalog Item 4 -->
                <article class="glass-panel rounded-2xl p-4 flex flex-col gap-4 group hover:bg-surface-container-high transition-colors glow-border">
                    <div class="aspect-square rounded-xl overflow-hidden relative">
                        <div class="absolute inset-0 bg-cover bg-center transition-transform duration-500 group-hover:scale-105"
                             style="background-image: url('https://images.unsplash.com/photo-1494232410401-ad00d5433cfa?w=400&q=80');"></div>
                        <span class="absolute top-2 left-2 bg-tertiary-container text-on-tertiary-container text-xs px-2 py-0.5 rounded-full font-label-md uppercase tracking-wider">{{ __('messages.limited') }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <h3 class="font-label-md text-label-md text-on-background truncate group-hover:text-primary-fixed transition-colors">{{ __('messages.catalog_item_4') }}</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant text-sm truncate">{{ __('messages.catalog_item_4_artist') }}</p>
                    </div>
                    <div class="flex justify-between items-center mt-auto">
                        <span class="font-headline-md text-headline-md text-on-background text-lg">8,000 FCFA</span>
                        <button type="button" class="w-8 h-8 rounded-full bg-surface-container border border-outline-variant text-on-surface flex items-center justify-center group-hover:border-primary-fixed group-hover:text-primary-fixed transition-colors">
                            <span class="material-symbols-outlined text-[18px]">add</span>
                        </button>
                    </div>
                </article>
            </div>
        </section>
    </div>
</x-layouts::public>
