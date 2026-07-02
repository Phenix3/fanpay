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
                {{ __('Welcome Back') }}
            </h1>
            <p class="text-on-surface-variant font-body-md mt-2">
                {{ __('Prêt à retrouver le rythme de la nation ?') }}
            </p>
        </div>
    </div>

    <!-- Session Status -->
    @if (session('status'))
        <div class="font-body-md text-sm text-primary-fixed text-center bg-primary-fixed/10 rounded-xl py-3 px-4 border border-primary-fixed/20">
            {{ session('status') }}
        </div>
    @endif

    <!-- Passkey Verification -->
    <x-passkey-verify />

    <!-- Login Form -->
    <form method="POST" action="{{ route('login.store') }}" class="space-y-6">
        @csrf

        <div class="space-y-4">
            <!-- Email Input -->
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

            <!-- Password Input -->
            <div class="space-y-2 group">
                <label class="font-label-md text-label-md text-on-surface-variant ml-1" for="password">
                    {{ __('Password') }}
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
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                    <button
                        type="button"
                        class="absolute right-4 text-outline hover:text-primary transition-colors"
                        onclick="togglePasswordVisibility()"
                        aria-label="{{ __('Toggle password visibility') }}"
                    >
                        <span class="material-symbols-outlined" id="password-icon">visibility</span>
                    </button>
                </div>
                @error('password')
                    <p class="font-body-md text-sm text-error mt-1 ml-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Utilities: Remember Me + Forgot Password -->
        <div class="flex items-center justify-between font-label-md text-label-md">
            <label class="flex items-center space-x-3 cursor-pointer group">
                <div class="relative flex items-center">
                    <input
                        type="checkbox"
                        name="remember"
                        id="remember"
                        {{ old('remember') ? 'checked' : '' }}
                        class="peer hidden"
                    />
                    <div class="w-5 h-5 border-2 border-outline rounded flex items-center justify-center transition-all peer-checked:bg-primary-container peer-checked:border-primary-container">
                        <span class="material-symbols-outlined text-on-primary-container text-[16px] hidden peer-checked:block"
                              style="font-variation-settings: 'wght' 700;">check</span>
                    </div>
                </div>
                <span class="text-on-surface-variant group-hover:text-on-surface transition-colors">
                    {{ __('Remember me') }}
                </span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-primary-fixed hover:text-primary-container transition-colors font-semibold" href="{{ route('password.request') }}" wire:navigate>
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <!-- Submit Button -->
        <button
            type="submit"
            class="w-full bg-primary-fixed-dim text-on-primary py-5 rounded-xl font-headline-md text-headline-md neon-btn-glow flex items-center justify-center gap-2 hover:brightness-110 active:scale-[0.98] transition-all duration-200"
        >
            {{ __('Log in') }}
            <span class="material-symbols-outlined">arrow_forward</span>
        </button>
    </form>

    <!-- Divider -->
    <div class="relative flex items-center py-4">
        <div class="flex-grow border-t border-outline-variant"></div>
        <span class="flex-shrink mx-4 text-outline font-label-md text-label-md uppercase tracking-widest">{{ __('Or continue with') }}</span>
        <div class="flex-grow border-t border-outline-variant"></div>
    </div>

    <!-- Social Logins -->
    <div class="grid grid-cols-3 gap-4">
        <button type="button" class="glass-card flex items-center justify-center py-3 rounded-xl hover:bg-surface-container transition-all group">
            <svg class="w-6 h-6 text-on-surface group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12.48 10.92v3.28h7.84c-.24 1.84-2.21 5.39-7.84 5.39-4.84 0-8.75-4.01-8.75-8.96s3.91-8.96 8.75-8.96c2.75 0 4.59 1.16 5.66 2.18l2.59-2.5C18.66 1.18 15.82 0 12.48 0 5.58 0 0 5.58 0 12.48s5.58 12.48 12.48 12.48c7.21 0 12-5.06 12-12.21 0-.82-.09-1.44-.24-2.07H12.48z"/>
            </svg>
        </button>
        <button type="button" class="glass-card flex items-center justify-center py-3 rounded-xl hover:bg-surface-container transition-all group">
            <svg class="w-6 h-6 text-on-surface group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
            </svg>
        </button>
        <button type="button" class="glass-card flex items-center justify-center py-3 rounded-xl hover:bg-surface-container transition-all group">
            <svg class="w-6 h-6 text-on-surface group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12c0-5.523-4.477-10-10-10z"/>
            </svg>
        </button>
    </div>

    <!-- Sign up link -->
    <p class="text-center font-body-md text-on-surface-variant">
        <span>{{ __('Don\'t have an account?') }}</span>
        <a class="text-primary-fixed hover:underline font-bold" href="{{ route('register') }}" wire:navigate>
            {{ __('Sign up') }}
        </a>
    </p>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const icon = document.getElementById('password-icon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                passwordInput.type = 'password';
                icon.textContent = 'visibility';
            }
        }
    </script>
</x-layouts::public-auth>
