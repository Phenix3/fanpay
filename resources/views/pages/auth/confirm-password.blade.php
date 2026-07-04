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
                {{ __('messages.confirm_password_title') }}
            </h1>
            <p class="text-on-surface-variant font-body-md mt-2">
                {{ __('messages.secure_area_description') }}
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
    <x-passkey-verify
        options-route="passkey.confirm-options"
        submit-route="passkey.confirm"
        :label="__('Confirm with passkey')"
        :loading-label="__('Confirming...')"
        :separator="__('Or confirm with password')"
    />

    <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-6">
        @csrf

        <!-- Password -->
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
                    aria-label="{{ __('messages.toggle_password_visibility') }}"
                >
                    <span class="material-symbols-outlined" id="password-icon">visibility</span>
                </button>
            </div>
            @error('password')
                <p class="font-body-md text-sm text-error mt-1 ml-1">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="w-full bg-primary-fixed-dim text-on-primary py-5 rounded-xl font-headline-md text-headline-md neon-btn-glow flex items-center justify-center gap-2 hover:brightness-110 active:scale-[0.98] transition-all duration-200"
            data-test="confirm-password-button"
        >
            <span>{{ __('messages.confirm') }}</span>
            <span class="material-symbols-outlined">verified</span>
        </button>
    </form>

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
