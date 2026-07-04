<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <style>
            body {
                background-color: var(--color-background);
                color: var(--color-on-background);
                font-family: 'Plus Jakarta Sans', sans-serif;
                margin: 0;
                padding: 0;
            }
        </style>
    </head>
    <body class="antialiased selection:bg-primary-fixed selection:text-on-primary-fixed min-h-screen flex flex-col">
        <div class="public-layout flex-grow flex flex-col">
            <!-- TopNavBar (Desktop) -->
            <nav
                class="hidden md:flex fixed top-0 w-full z-50 bg-background/80 backdrop-blur-xl border-b border-outline-variant/20 shadow-sm justify-between items-center px-margin-desktop h-20 transition-all duration-300"
                id="topNav"
            >
                <div class="flex items-center gap-8">
                    <a href="{{ route('home') }}" class="font-headline-md text-headline-md font-bold text-primary-fixed hover:text-primary-fixed-dim transition-colors">
                        {{ config('app.name', 'MboaSound') }}
                    </a>
                    <div class="flex gap-6">
                        <a
                            class="font-label-md text-label-md text-on-surface-variant hover:text-primary-fixed-dim transition-colors active:scale-95 transition-transform"
                            href="{{ route('artists.index') }}" wire:navigate
                        >{{ __('messages.explorer') }}</a>
                        <a
                            class="font-label-md text-label-md text-on-surface-variant hover:text-primary-fixed-dim transition-colors active:scale-95 transition-transform"
                            href="{{ route('events.index') }}" wire:navigate
                        >{{ __('messages.events_nav') }}</a>
                        <a
                            class="font-label-md text-label-md text-on-surface-variant hover:text-primary-fixed-dim transition-colors active:scale-95 transition-transform"
                            href="{{ route('supervote') }}" wire:navigate
                        >{{ __('messages.supervote') }}</a><a class="font-label-md text-label-md text-on-surface-variant hover:text-primary-fixed-dim transition-colors active:scale-95 transition-transform" href="{{ route('boutique') }}" wire:navigate>{{ __('messages.boutique') }}</a>
                    </div>
                </div>
                <div class="flex items-center gap-6">
                    @auth
                        <button
                            class="text-on-surface-variant hover:text-primary-fixed-dim transition-colors active:scale-95 transition-transform"
                            onclick="window.location.href='{{ route('dashboard') }}'"
                        >
                            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 0">dashboard</span>
                        </button>
                        <div
                            class="w-10 h-10 rounded-full bg-surface-container-high border border-outline-variant overflow-hidden cursor-pointer active:scale-95 transition-transform"
                            onclick="window.location.href='{{ route('dashboard') }}'"
                        >
                            <div class="w-full h-full flex items-center justify-center text-on-surface font-label-md text-label-md">
                                {{ auth()->user()->initials() }}
                            </div>
                        </div>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="font-label-md text-label-md text-on-surface-variant hover:text-primary-fixed transition-colors"
                        >{{ __('messages.login') }}</a>
                        <a
                            href="{{ route('register') }}"
                            class="px-5 py-2.5 bg-primary-fixed text-on-primary-fixed rounded-xl font-label-md text-label-md hover:bg-primary-fixed-dim active:scale-95 transition-all"
                        >{{ __('messages.sinscrire') }}</a>
                    @endauth
                </div>
            </nav>

            <!-- Main Content -->
            <main class="flex-grow flex flex-col md:pt-20 pb-32 md:pb-0">
                {{ $slot }}
            </main>

            <!-- BottomNavBar (Mobile) -->
            <nav
                class="fixed bottom-0 left-0 w-full z-50 flex justify-around items-center px-4 pb-8 pt-2 md:hidden bg-background/90 backdrop-blur-xl border-t border-primary-fixed/30 shadow-[0_-8px_24px_rgba(0,227,139,0.15)] rounded-t-xl"
            >
                <a class="flex flex-col items-center justify-center text-primary-container rounded-2xl p-2 px-4 active:scale-90 transition-all" href="{{ route('artists.index') }}" wire:navigate>
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1">explore</span>
                    <span class="font-label-md text-label-md mt-1">{{ __('messages.explorer') }}</span>
                </a>
                <a class="flex flex-col items-center justify-center text-primary-container active:scale-90 transition-all" href="{{ route('supervote') }}" wire:navigate>
                    <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1">how_to_vote</span>
                    <span class="font-label-md text-label-md mt-1">{{ __('messages.vote_page') }}</span>
                </a>
                <a class="flex flex-col items-center justify-center text-on-surface-variant hover:text-primary-fixed active:scale-90 transition-all" href="{{ route('events.index') }}" wire:navigate>
                    <span class="material-symbols-outlined">event</span>
                    <span class="font-label-md text-label-md mt-1">{{ __('messages.events_nav') }}</span>
                </a>
                <a class="flex flex-col items-center justify-center text-on-surface-variant hover:text-primary-fixed active:scale-90 transition-all" href="#">
                    <span class="material-symbols-outlined">person</span>
                    <span class="font-label-md text-label-md mt-1">{{ __('messages.profile') }}</span>
                </a>
            </nav>

            <!-- Footer (Desktop only) -->
            <footer
                class="bg-surface-container-lowest w-full py-12 border-t border-outline-variant/10 hidden md:flex flex-col md:flex-row justify-between items-center px-margin-desktop gap-8"
            >
                <div class="font-body-md text-body-md text-on-surface-variant">
                    &copy; {{ date('Y') }} {{ config('app.name', 'MboaSound') }}. {{ __('messages.electric_premium_experience') }}
                </div>
                <div class="flex gap-6">
                    <a class="font-body-md text-body-md text-on-surface-variant hover:text-primary-fixed underline opacity-80 hover:opacity-100 transition-opacity" href="#">{{ __('messages.terms') }}</a>
                    <a class="font-body-md text-body-md text-on-surface-variant hover:text-primary-fixed underline opacity-80 hover:opacity-100 transition-opacity" href="#">{{ __('messages.privacy') }}</a>
                    <a class="font-body-md text-body-md text-on-surface-variant hover:text-primary-fixed underline opacity-80 hover:opacity-100 transition-opacity" href="#">{{ __('messages.artist_faq') }}</a>
                    <a class="font-body-md text-body-md text-on-surface-variant hover:text-primary-fixed underline opacity-80 hover:opacity-100 transition-opacity" href="#">{{ __('messages.contact') }}</a>
                </div>
            </footer>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts

        <script>
            // Scroll effect for top nav
            window.addEventListener('scroll', () => {
                const nav = document.getElementById('topNav');
                if (window.scrollY > 50) {
                    nav.classList.add('shadow-lg', 'bg-background/95');
                    nav.classList.remove('bg-background/80', 'shadow-sm');
                } else {
                    nav.classList.remove('shadow-lg', 'bg-background/95');
                    nav.classList.add('bg-background/80', 'shadow-sm');
                }
            });
        </script>
    </body>
</html>
