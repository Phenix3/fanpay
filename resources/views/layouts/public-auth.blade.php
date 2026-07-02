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
    <body class="overflow-x-hidden">
        <div class="public-layout min-h-screen w-full flex flex-col md:flex-row">
            <!-- Left Side: Hero Visual (hidden on mobile) -->
            <section class="hidden md:flex relative w-1/2 h-screen overflow-hidden">
                <div
                    class="absolute inset-0 z-0 bg-cover bg-center transition-transform duration-10000 hover:scale-110"
                    style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuChUHY7jdtyPMdYBYnz3D6JlyGM5TlAONg-HlEBn5_T_J56an-tU_ONaHqNJvMiw6D1sUmcah7zvuM1MS--HoJmCFq69e2-WVyyGzU3Zcda-Mn3arhmuqiF6g67iZo68OHvVGnDDmPy6O6_szB0SA3pExKSkmwUuQXCV1ZK2BQQZe91rzwhNnSMAAbMEF9np1d9tVempc7sSCrKYKDlq1KT_ny-3CrvnbniNd_pR_SubpxT7W-bCMK6V6BVF7hxewPhjNYualCpm5f6');"
                ></div>
                <!-- Gradient overlays for contrast -->
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-transparent to-surface z-10"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-surface/80 via-transparent to-transparent z-10"></div>

                <!-- Floating branding element -->
                <div class="absolute bottom-12 left-12 z-20 max-w-md">
                    <span class="inline-block px-4 py-1 rounded-full bg-primary-container text-on-primary-container font-label-md text-label-md mb-4">
                        {{ __('DIRECT DU CAMEROUN') }}
                    </span>
                    <h2 class="font-display-lg text-display-lg text-primary leading-tight">
                        {{ __('LA MUSIQUE') }}<br />{{ __('INDOMPTABLE.') }}
                    </h2>
                </div>
            </section>

            <!-- Right Side: Auth Form -->
            <section class="w-full md:w-1/2 min-h-screen bg-surface flex flex-col items-center justify-center px-6 md:px-margin-desktop py-12 relative overflow-y-auto bg-mesh">
                <!-- Ambient glow effects -->
                <div class="absolute top-0 right-0 w-96 h-96 bg-primary-fixed/5 blur-[120px] -z-10 rounded-full"></div>
                <div class="absolute bottom-0 left-0 w-72 h-72 bg-secondary-container/5 blur-[100px] -z-10 rounded-full"></div>

                <div class="w-full max-w-md space-y-10 z-20">
                    {{ $slot }}
                </div>
            </section>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
