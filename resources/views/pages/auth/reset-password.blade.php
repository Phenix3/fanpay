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
                {{ __('messages.reset_password') }}
            </h1>
            <p class="text-on-surface-variant font-body-md mt-2">
                {{ __('messages.enter_new_password_below') }}
            </p>
        </div>
    </div>

    <!-- Session Status -->
    @if (session('status'))
        <div class="font-body-md text-sm text-primary-fixed text-center bg-primary-fixed/10 rounded-xl py-3 px-4 border border-primary-fixed/20">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="token" value="{{ request()->route('token') }}">

        <!-- Email (readonly, pre-filled from the reset link) -->
        <div class="space-y-2 group">
            <label class="font-label-md text-label-md text-on-surface-variant ml-1" for="email">
                {{ __('Email address') }}
            </label>
            <div class="relative flex items-center glass-card rounded-xl glow-on-focus transition-all duration-300">
                <span class="material-symbols-outlined absolute left-4 text-outline"
                      style="font-variation-settings: 'wght' 300;">mail</span>
                <input
                    class="w-full bg-transparent border-none py-4 pl-12 pr-4 text-on-surface focus:ring-0 placeholder:text-outline-variant font-body-md"
                    id="email"
                    name="email"
                    type="email"
                    value="{{ request('email') }}"
                    readonly
                    autocomplete="email"
                />
            </div>
        </div>

        <!-- New Password -->
        <div class="space-y-2 group">
            <label class="font-label-md text-label-md text-on-surface-variant ml-1" for="password">
                {{ __('messages.new_password') }}
            </label>
            <div class="relative flex items-center glass-card rounded-xl glow-on-focus transition-all duration-300 @error('password') !border-error/50 !shadow-[0_0_15px_rgba(255,180,171,0.3)] @enderror">
                <span class="material-symbols-outlined absolute left-4 text-outline"
                      style="font-variation-settings: 'wght' 300;">lock</span>
                <input
                    class="w-full bg-transparent border-none py-4 pl-12 pr-12 text-on-surface focus:ring-0 placeholder:text-outline-variant font-body-md"
                    id="password"
                    name="password"
                    type="password"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                />
                <button
                    type="button"
                    class="absolute right-4 text-outline hover:text-primary transition-colors"
                    onclick="togglePasswordVisibility('password', 'password-icon')"
                    aria-label="{{ __('messages.toggle_password_visibility') }}"
                >
                    <span class="material-symbols-outlined" id="password-icon">visibility</span>
                </button>
            </div>
            @error('password')
                <p class="font-body-md text-sm text-error mt-1 ml-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Confirm New Password -->
        <div class="space-y-2 group">
            <label class="font-label-md text-label-md text-on-surface-variant ml-1" for="password_confirmation">
                {{ __('messages.confirm_password') }}
            </label>
            <div class="relative flex items-center glass-card rounded-xl glow-on-focus transition-all duration-300 @error('password_confirmation') !border-error/50 !shadow-[0_0_15px_rgba(255,180,171,0.3)] @enderror">
                <span class="material-symbols-outlined absolute left-4 text-outline"
                      style="font-variation-settings: 'wght' 300;">lock</span>
                <input
                    class="w-full bg-transparent border-none py-4 pl-12 pr-12 text-on-surface focus:ring-0 placeholder:text-outline-variant font-body-md"
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                />
                <button
                    type="button"
                    class="absolute right-4 text-outline hover:text-primary transition-colors"
                    onclick="togglePasswordVisibility('password_confirmation', 'password-confirm-icon')"
                    aria-label="{{ __('messages.toggle_password_visibility') }}"
                >
                    <span class="material-symbols-outlined" id="password-confirm-icon">visibility</span>
                </button>
            </div>
            @error('password_confirmation')
                <p class="font-body-md text-sm text-error mt-1 ml-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password Rules Hint -->
        <p class="font-label-md text-label-md text-on-surface-variant text-xs">
            {{ __('messages.password_rules_hint') }}
        </p>

        <button
            type="submit"
            class="w-full bg-primary-fixed-dim text-on-primary py-5 rounded-xl font-headline-md text-headline-md neon-btn-glow flex items-center justify-center gap-2 hover:brightness-110 active:scale-[0.98] transition-all duration-200"
            data-test="reset-password-button"
        >
            <span>{{ __('messages.reset_password_btn') }}</span>
            <span class="material-symbols-outlined">lock_reset</span>
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

    <script>
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                input.type = 'password';
                icon.textContent = 'visibility';
            }
        }
    </script>
</x-layouts::public-auth>
