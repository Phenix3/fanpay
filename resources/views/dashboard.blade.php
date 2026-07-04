<x-layouts::app :title="__('messages.dashboard')">
    <div class="relative min-h-full">
        {{-- Background glow --}}
        <div class="bg-glow"></div>

        @if(auth()->user()->isArtist())
            {{-- ============================================================= --}}
            {{-- ARTIST DASHBOARD — Bento Grid Design                         --}}
            {{-- ============================================================= --}}

            {{-- Header --}}
            <header class="mb-8 md:mb-12 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
                <div>
                    <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-primary-fixed mb-2 neon-glow">
                        {{ __('messages.overview') }}
                    </h1>
                    <p class="font-body-lg text-body-lg text-on-surface-variant">
                        {{ __('messages.dashboard_performance_subtitle') }}
                    </p>
                </div>
                <div class="hidden md:flex gap-4">
                    <button
                        type="button"
                        class="glass-panel p-3 rounded-full hover:bg-surface-container-highest transition-colors flex items-center justify-center text-on-surface"
                        aria-label="{{ __('messages.notifications') }}"
                    >
                        <span class="material-symbols-outlined">notifications</span>
                    </button>
                    <a
                        href="{{ route('settings') }}"
                        class="glass-panel p-3 rounded-full hover:bg-surface-container-highest transition-colors flex items-center justify-center text-on-surface"
                        aria-label="{{ __('messages.settings') }}"
                        wire:navigate
                    >
                        <span class="material-symbols-outlined">settings</span>
                    </a>
                </div>
            </header>

            {{-- Quick Actions --}}
            <div class="flex flex-wrap gap-3 mb-8">
                <a
                    href="{{ route('artist.catalog') }}"
                    class="bg-primary-container text-on-primary-container px-5 py-3 rounded-xl font-label-md text-label-md font-bold hover:brightness-110 transition-all flex items-center gap-2"
                    wire:navigate
                >
                    <span class="material-symbols-outlined">upload_file</span>
                    {{ __('messages.upload_new_track') }}
                </a>
                <a
                    href="#"
                    class="px-5 py-3 rounded-xl font-label-md text-label-md font-bold border border-outline-variant text-on-surface-variant hover:bg-surface-container-high transition-all flex items-center gap-2"
                >
                    <span class="material-symbols-outlined">monitoring</span>
                    {{ __('messages.analytics') }}
                </a>
            </div>

            {{-- Bento Grid --}}
            <div class="grid grid-cols-1 md:grid-cols-12 gap-gutter">

                {{-- Balance Card (8 cols) --}}
                <div class="glass-panel rounded-xl p-6 md:p-8 col-span-1 md:col-span-8 flex flex-col justify-between relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-56 md:w-72 h-56 md:h-72 bg-primary-container/10 blur-[50px] rounded-full -translate-y-1/2 translate-x-1/4 pointer-events-none"></div>
                    <div class="flex justify-between items-start mb-8 relative z-10">
                        <div>
                            <h3 class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider mb-2">
                                {{ __('messages.available_balance') }}
                            </h3>
                            {{-- TODO: Replace with real wallet balance --}}
                            <div class="font-display-lg text-display-lg text-primary-container font-bold neon-glow leading-none">
                                {{ number_format(24500, 0, ',', ' ') }}<span class="text-3xl text-on-surface-variant ml-2">FCFA</span>
                            </div>
                        </div>
                        <div class="p-3 bg-surface-container rounded-lg border border-outline-variant/30 text-primary-container shrink-0">
                            <span class="material-symbols-outlined text-3xl">account_balance_wallet</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-4 relative z-10">
                        <a
                            href="{{ route('artist.wallet') }}"
                            class="bg-primary-container text-on-primary-container px-5 py-3 rounded-xl font-label-md text-label-md font-bold hover:brightness-110 transition-all flex items-center gap-2"
                            wire:navigate
                        >
                            <span class="material-symbols-outlined">account_balance</span>
                            {{ __('messages.withdraw_funds') }}
                        </a>
                        <a
                            href="{{ route('artist.wallet') }}"
                            class="px-5 py-3 rounded-xl font-label-md text-label-md font-bold border border-primary-container text-primary-container hover:bg-primary-container/10 transition-all flex items-center gap-2"
                            wire:navigate
                        >
                            <span class="material-symbols-outlined">receipt_long</span>
                            {{ __('messages.statements') }}
                        </a>
                    </div>
                </div>

                {{-- Streams Card (4 cols) --}}
                <div class="glass-panel rounded-xl p-6 col-span-1 md:col-span-4 flex flex-col justify-between">
                    <div class="flex justify-between items-start mb-6">
                        <h3 class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">
                            {{ __('messages.total_streams') }}
                        </h3>
                        <span class="material-symbols-outlined text-secondary-container">spatial_audio</span>
                    </div>
                    {{-- TODO: Replace with real streaming stats --}}
                    <div>
                        <div class="font-headline-lg-mobile text-headline-lg-mobile font-bold text-on-surface mb-2">
                            1.2M
                        </div>
                        <div class="flex items-center gap-2 text-primary-container font-label-md text-label-md">
                            <span class="material-symbols-outlined text-sm">trending_up</span>
                            <span>+15.4% {{ __('messages.this_month') }}</span>
                        </div>
                    </div>
                    {{-- Mini bar chart --}}
                    <div class="mt-6 flex items-end gap-1.5 md:gap-2 h-16 w-full opacity-60">
                        <div class="w-full bg-secondary-container/20 h-[30%] rounded-t-sm"></div>
                        <div class="w-full bg-secondary-container/30 h-[45%] rounded-t-sm"></div>
                        <div class="w-full bg-secondary-container/40 h-[25%] rounded-t-sm"></div>
                        <div class="w-full bg-secondary-container/50 h-[60%] rounded-t-sm"></div>
                        <div class="w-full bg-secondary-container/70 h-[50%] rounded-t-sm"></div>
                        <div class="w-full bg-secondary-container/90 h-[80%] rounded-t-sm"></div>
                        <div class="w-full bg-secondary-container h-[100%] rounded-t-sm shadow-[0_0_10px_rgba(255,86,44,0.5)]"></div>
                    </div>
                </div>

                {{-- Recent Activity (6 cols) --}}
                <div class="glass-panel rounded-xl p-6 col-span-1 md:col-span-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-headline-md text-headline-md text-on-surface">
                            {{ __('messages.recent_activity') }}
                        </h3>
                        <a href="{{ route('artist.wallet') }}" class="font-label-md text-label-md text-primary-fixed hover:underline" wire:navigate>
                            {{ __('messages.view_all') }}
                        </a>
                    </div>
                    <div class="flex flex-col gap-3">
                        {{-- Transaction 1 --}}
                        <div class="flex items-center justify-between p-3 md:p-4 bg-surface-container-low rounded-lg border border-outline-variant/10 hover:bg-surface-container transition-colors group">
                            <div class="flex items-center gap-3 md:gap-4 min-w-0">
                                <div class="w-10 h-10 rounded-full bg-tertiary-container/20 flex items-center justify-center text-tertiary-container shrink-0 group-hover:shadow-[0_0_10px_rgba(255,221,86,0.3)] transition-shadow">
                                    <span class="material-symbols-outlined text-[20px]">music_note</span>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-body-md text-body-md font-bold text-on-surface truncate">
                                        {{ __('messages.royalties_apple_music') }}
                                    </h4>
                                    <p class="font-label-md text-label-md text-on-surface-variant">
                                        {{ __('messages.today_at') }}, 09:41
                                    </p>
                                </div>
                            </div>
                            <div class="text-right shrink-0 ml-3">
                                <div class="font-body-md text-body-md font-bold text-primary-container">+1 250 FCFA</div>
                                <span class="px-2 py-0.5 rounded-full bg-primary-container/10 text-primary-container text-xs font-bold border border-primary-container/20">
                                    {{ __('messages.completed') }}
                                </span>
                            </div>
                        </div>

                        {{-- Transaction 2 --}}
                        <div class="flex items-center justify-between p-3 md:p-4 bg-surface-container-low rounded-lg border border-outline-variant/10 hover:bg-surface-container transition-colors group">
                            <div class="flex items-center gap-3 md:gap-4 min-w-0">
                                <div class="w-10 h-10 rounded-full bg-secondary-container/20 flex items-center justify-center text-secondary-container shrink-0 group-hover:shadow-[0_0_10px_rgba(255,86,44,0.3)] transition-shadow">
                                    <span class="material-symbols-outlined text-[20px]">shopping_cart</span>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-body-md text-body-md font-bold text-on-surface truncate">
                                        {{ __('messages.merch_store_payout') }}
                                    </h4>
                                    <p class="font-label-md text-label-md text-on-surface-variant">
                                        {{ __('messages.yesterday') }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right shrink-0 ml-3">
                                <div class="font-body-md text-body-md font-bold text-primary-container">+450 FCFA</div>
                                <span class="px-2 py-0.5 rounded-full bg-primary-container/10 text-primary-container text-xs font-bold border border-primary-container/20">
                                    {{ __('messages.completed') }}
                                </span>
                            </div>
                        </div>

                        {{-- Transaction 3 --}}
                        <div class="flex items-center justify-between p-3 md:p-4 bg-surface-container-low rounded-lg border border-outline-variant/10 hover:bg-surface-container transition-colors group">
                            <div class="flex items-center gap-3 md:gap-4 min-w-0">
                                <div class="w-10 h-10 rounded-full bg-surface-variant flex items-center justify-center text-on-surface-variant shrink-0">
                                    <span class="material-symbols-outlined text-[20px]">account_balance</span>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-body-md text-body-md font-bold text-on-surface truncate">
                                        {{ __('messages.bank_withdrawal') }}
                                    </h4>
                                    <p class="font-label-md text-label-md text-on-surface-variant">
                                        12 Oct 2023
                                    </p>
                                </div>
                            </div>
                            <div class="text-right shrink-0 ml-3">
                                <div class="font-body-md text-body-md font-bold text-on-surface">- 5 000 FCFA</div>
                                <span class="px-2 py-0.5 rounded-full bg-surface-variant text-on-surface-variant text-xs font-bold">
                                    {{ __('messages.processed') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Top Track Promo (6 cols) --}}
                <div class="glass-panel rounded-xl p-0 col-span-1 md:col-span-6 overflow-hidden relative min-h-[280px] md:min-h-[320px] flex items-end">
                    <div class="absolute inset-0 bg-cover bg-center opacity-40 z-0"
                         style="background-image: url('https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?q=80&w=2070&auto=format&fit=crop');">
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-t from-background via-background/80 to-transparent z-10"></div>
                    <div class="relative z-20 p-6 md:p-8 w-full">
                        <span class="px-3 py-1 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-md text-label-md font-bold text-xs uppercase tracking-widest mb-4 inline-block shadow-[0_0_15px_rgba(255,225,112,0.4)]">
                            {{ __('messages.top_performing_track') }}
                        </span>
                        {{-- TODO: Replace with real top track data --}}
                        <h3 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-surface font-bold mb-2">
                            Midnight Sahel
                        </h3>
                        <p class="font-body-md text-body-md text-on-surface-variant mb-6 max-w-md">
                            {{ __('messages.top_track_description') }}
                        </p>
                        <button type="button" class="bg-primary-container text-on-primary-container px-5 py-3 rounded-xl font-label-md text-label-md font-bold hover:brightness-110 transition-all flex items-center gap-2 w-max">
                            <span class="material-symbols-outlined">campaign</span>
                            {{ __('messages.boost_track') }}
                        </button>
                    </div>
                </div>

            </div>

        @elseif(auth()->user()->isAdmin())
            {{-- ============================================================= --}}
            {{-- ADMIN DASHBOARD — Simplified                                 --}}
            {{-- ============================================================= --}}
            <div class="flex flex-col gap-6">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-on-surface">
                        {{ __('messages.welcome') }}, {{ auth()->user()->name }}!
                    </h1>
                    <p class="font-body-md text-body-md text-on-surface-variant">
                        {{ __('messages.admin_dashboard_subtitle') }}
                    </p>
                </div>
                <div class="glass-panel rounded-xl p-8 text-center">
                    <span class="material-symbols-outlined text-6xl text-on-surface-variant mb-4">admin_panel_settings</span>
                    <h2 class="font-headline-md text-headline-md text-on-surface mb-2">{{ __('messages.admin_panel') }}</h2>
                    <p class="font-body-md text-body-md text-on-surface-variant mb-6">{{ __('messages.admin_dashboard_desc') }}</p>
                    <a href="/admin" class="inline-flex items-center gap-2 bg-primary-container text-on-primary-container px-6 py-3 rounded-xl font-label-md text-label-md font-bold hover:brightness-110 transition-all">
                        <span class="material-symbols-outlined">admin_panel_settings</span>
                        {{ __('messages.access_admin') }}
                    </a>
                </div>
            </div>

        @else
            {{-- ============================================================= --}}
            {{-- FAN DASHBOARD — Simplified                                   --}}
            {{-- ============================================================= --}}
            <div class="flex flex-col gap-6">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-on-surface">
                        {{ __('messages.welcome') }}, {{ auth()->user()->name }}!
                    </h1>
                    <p class="font-body-md text-body-md text-on-surface-variant">
                        {{ __('messages.fan_dashboard_subtitle') }}
                    </p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
                    <div class="glass-panel rounded-xl p-6 text-center hover:bg-surface-container transition-colors">
                        <span class="material-symbols-outlined text-4xl text-primary-fixed mb-3">library_music</span>
                        <h3 class="font-headline-md text-headline-md text-on-surface mb-1">{{ __('messages.my_library') }}</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant text-sm">{{ __('messages.fan_library_desc') }}</p>
                    </div>
                    <div class="glass-panel rounded-xl p-6 text-center hover:bg-surface-container transition-colors">
                        <span class="material-symbols-outlined text-4xl text-secondary-container mb-3">confirmation_number</span>
                        <h3 class="font-headline-md text-headline-md text-on-surface mb-1">{{ __('messages.my_tickets') }}</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant text-sm">{{ __('messages.fan_tickets_desc') }}</p>
                    </div>
                    <div class="glass-panel rounded-xl p-6 text-center hover:bg-surface-container transition-colors">
                        <span class="material-symbols-outlined text-4xl text-tertiary-fixed mb-3">trophy</span>
                        <h3 class="font-headline-md text-headline-md text-on-surface mb-1">{{ __('messages.contests') }}</h3>
                        <p class="font-body-md text-body-md text-on-surface-variant text-sm">{{ __('messages.fan_contests_desc') }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts::app>
