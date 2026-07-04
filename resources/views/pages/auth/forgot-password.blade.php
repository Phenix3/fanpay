<x-layouts::public-auth>
    <!-- Brand Identity -->
    <div class="flex flex-col items-center md:items-start space-y-4">
        <div class="flex items-center gap-2 group">
            <span class="material-symbols-outlined text-primary-fixed text-4xl transform transition-transform group-hover:rotate-12"
                  style="font-variation-settings: 'FILL' 1;">music_note</span>
            <span class="font-headline-md text-headline-md font-bold tracking-tight text-primary">
                {{ config('app.name', 'MboaSound') }}
            </span>
        </div>
        <div class="text-center md:text-left">
            <h1 class="font-headline-lg text-headline-lg text-on-surface">
                {{ __('messages.password_recovery') }}
            </h1>
            <p class="text-on-surface-variant font-body-md mt-2">
                {{ __('messages.enter_email_for_reset') }}
            </p>
        </div>
    </div>

    @if (session('status'))
        <!-- Success State -->
        <div class="space-y-8 text-center py-4 animate-success">
            <div class="flex justify-center">
                <div class="w-20 h-20 rounded-full bg-primary-fixed-dim/10 border border-primary-fixed-dim flex items-center justify-center">
                    <span class="material-symbols-outlined text-4xl text-primary-fixed-dim"
                          style="font-variation-settings: 'FILL' 1;">check_circle</span>
                </div>
            </div>
            <div class="space-y-2">
                <h2 class="font-headline-md text-headline-md text-on-surface">
                    {{ __('messages.link_sent') }}
                </h2>
                <p class="font-body-md text-body-md text-on-surface-variant">
                    {{ __('messages.reset_instructions_sent') }}
                </p>
            </div>
            <div class="p-4 bg-surface-container-highest/30 rounded-lg border border-outline-variant/50">
                <p class="font-label-md text-label-md text-on-surface-variant italic">
                    {{ __('messages.check_spam') }}
                </p>
            </div>
            <a href="{{ route('password.request') }}"
               class="w-full inline-flex items-center justify-center gap-2 py-4 border border-primary-fixed-dim text-primary-fixed-dim rounded-xl font-label-md font-bold hover:bg-primary-fixed-dim/10 transition-all"
               wire:navigate>
                <span>{{ __('messages.resend_email') }}</span>
                <span class="material-symbols-outlined">refresh</span>
            </a>
            <p class="text-center font-body-md text-on-surface-variant">
                <a class="inline-flex font-label-md text-label-md text-on-surface-variant hover:text-primary-fixed-dim transition-colors items-center gap-2"
                   href="{{ route('login') }}" wire:navigate>
                    <span class="material-symbols-outlined text-sm">arrow_back</span>
                    {{ __('messages.back_to_login') }}
                </a>
            </p>
        </div>
    @else
        <!-- Recovery Form -->
        <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
            @csrf

            <div class="space-y-2 group">
                <label class="font-label-md text-label-md text-on-surface-variant ml-1" for="email">
                    {{ __('Email address') }}
                </label>
                <div class="relative flex items-center glass-card rounded-xl glow-on-focus transition-all duration-300 @error('email') !border-error/50 !shadow-[0_0_15px_rgba(255,180,171,0.3)] @enderror">
                    <span class="material-symbols-outlined absolute left-4 text-outline"
                          style="font-variation-settings: 'wght' 300;">mail</span>
                    <input
                        class="w-full bg-transparent border-none py-4 pl-12 pr-4 text-on-surface focus:ring-0 placeholder:text-outline-variant font-body-md"
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="nom@exemple.cm"
                    />
                </div>
                @error('email')
                    <p class="font-body-md text-sm text-error mt-1 ml-1">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full bg-primary-fixed-dim text-on-primary py-5 rounded-xl font-headline-md text-headline-md neon-btn-glow flex items-center justify-center gap-2 hover:brightness-110 active:scale-[0.98] transition-all duration-200"
            >
                <span>{{ __('messages.send_reset_link') }}</span>
                <span class="material-symbols-outlined">send</span>
            </button>
        </form>

        <!-- Back to login link -->
        <p class="text-center font-body-md text-on-surface-variant pt-4 border-t border-outline-variant">
            <a class="inline-flex font-label-md text-label-md text-on-surface-variant hover:text-primary-fixed-dim transition-colors items-center gap-2"
               href="{{ route('login') }}" wire:navigate>
                <span class="material-symbols-outlined text-sm">arrow_back</span>
                {{ __('messages.back_to_login') }}
            </a>
        </p>

        <!-- Already have an account -->
        <p class="text-center font-body-md text-on-surface-variant">
            <span>{{ __('messages.remember_password') }}</span>
            <a class="text-primary-fixed hover:underline font-bold" href="{{ route('login') }}" wire:navigate>
                {{ __('messages.login') }}
            </a>
        </p>
    @endif

    <style>
        @keyframes slideUpFade {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-success {
            animation: slideUpFade 0.5s ease forwards;
        }
    </style>
</x-layouts::public-auth>
