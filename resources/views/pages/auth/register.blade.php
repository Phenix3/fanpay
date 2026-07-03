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
                {{ __('messages.create_your_account') }}
            </h1>
            <p class="text-on-surface-variant font-body-md mt-2">
                {{ __('messages.prêt_à_dompter_le_rythme') }}
            </p>
        </div>
    </div>

    <!-- Session Status -->
    @if (session('status'))
        <div class="font-body-md text-sm text-primary-fixed text-center bg-primary-fixed/10 rounded-xl py-3 px-4 border border-primary-fixed/20">
            {{ session('status') }}
        </div>
    @endif

    <!-- Registration Form -->
    <form method="POST" action="{{ route('register.store') }}" class="space-y-6">
        @csrf

        <!-- Account Type Toggle (Fan / Artiste) -->
        <div class="glass-card p-1 rounded-xl flex w-full">
            <button
                type="button"
                class="flex-1 py-3 px-6 rounded-lg font-label-md text-label-md transition-all duration-200 {{ old('role', 'fan') === 'fan' ? 'active-toggle' : 'text-on-surface-variant' }}"
                id="fan-toggle"
                onclick="setAccountType('fan')"
            >
                {{ __('messages.fan') }}
            </button>
            <button
                type="button"
                class="flex-1 py-3 px-6 rounded-lg font-label-md text-label-md transition-all duration-200 {{ old('role') === 'artist' ? 'active-toggle' : 'text-on-surface-variant' }} hover:text-on-surface"
                id="artiste-toggle"
                onclick="setAccountType('artiste')"
            >
                {{ __('messages.artist') }}
            </button>
        </div>
        <input type="hidden" name="role" id="role-input" value="fan">

        <div class="space-y-4">
            <!-- Full Name -->
            <div class="space-y-2 group">
                <label class="font-label-md text-label-md text-on-surface-variant ml-1" for="name">
                    {{ __('messages.name') }}
                </label>
                <div class="relative flex items-center glass-card rounded-xl glow-on-focus transition-all duration-300 @error('name') !border-error/50 !shadow-[0_0_15px_rgba(255,180,171,0.3)] @enderror">
                    <span class="material-symbols-outlined absolute left-4 text-outline"
                          style="font-variation-settings: 'wght' 300;">person</span>
                    <input
                        class="w-full bg-transparent border-none py-4 pl-12 pr-4 text-on-surface focus:ring-0 placeholder:text-outline-variant font-body-md"
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Jean-Pierre Nkoumou"
                    />
                </div>
                @error('name')
                    <p class="font-body-md text-sm text-error mt-1 ml-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div class="space-y-2 group">
                <label class="font-label-md text-label-md text-on-surface-variant ml-1" for="email">
                    {{ __('messages.email') }}
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
                        autocomplete="email"
                        placeholder="nom@exemple.cm"
                    />
                </div>
                @error('email')
                    <p class="font-body-md text-sm text-error mt-1 ml-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Phone -->
            <div class="space-y-2 group">
                <label class="font-label-md text-label-md text-on-surface-variant ml-1" for="phone">
                    {{ __('messages.phone') }}
                </label>
                <div class="relative flex items-center glass-card rounded-xl glow-on-focus transition-all duration-300 @error('phone') !border-error/50 !shadow-[0_0_15px_rgba(255,180,171,0.3)] @enderror">
                    <span class="material-symbols-outlined absolute left-4 text-outline"
                          style="font-variation-settings: 'wght' 300;">smartphone</span>
                    <input
                        class="w-full bg-transparent border-none py-4 pl-12 pr-4 text-on-surface focus:ring-0 placeholder:text-outline-variant font-body-md"
                        id="phone"
                        name="phone"
                        type="tel"
                        value="{{ old('phone') }}"
                        required
                        autocomplete="tel"
                        placeholder="691234567"
                        inputmode="numeric"
                        pattern="[0-9]{9}"
                        maxlength="9"
                    />
                </div>
                @error('phone')
                    <p class="font-body-md text-sm text-error mt-1 ml-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div class="space-y-2 group">
                <label class="font-label-md text-label-md text-on-surface-variant ml-1" for="password">
                    {{ __('messages.password') }}
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

            <!-- Confirm Password -->
            <div class="space-y-2 group">
                <label class="font-label-md text-label-md text-on-surface-variant ml-1" for="password_confirmation">
                    {{ __('messages.confirm_password') }}
                </label>
                <div class="relative flex items-center glass-card rounded-xl glow-on-focus transition-all duration-300">
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
                </div>
            </div>
        </div>

        <!-- Terms & Conditions -->
        <div class="flex items-start gap-3 py-2">
            <div class="relative flex items-center mt-1">
                <input
                    type="checkbox"
                    name="terms"
                    id="terms"
                    class="peer hidden"
                />
                <div class="w-5 h-5 border-2 border-outline rounded flex items-center justify-center transition-all peer-checked:bg-primary-container peer-checked:border-primary-container cursor-pointer"
                     onclick="document.getElementById('terms').click()">
                    <span class="material-symbols-outlined text-on-primary-container text-[16px] hidden peer-checked:block"
                          style="font-variation-settings: 'wght' 700;">check</span>
                </div>
            </div>
            <label class="font-body-md text-body-md text-on-surface-variant cursor-pointer" for="terms">
                {!! __('messages.accept_terms', [
                    'terms' => '<a class="text-primary-fixed hover:underline" href="#" onclick="event.preventDefault()">'.__('messages.terms_of_use').'</a>',
                    'privacy' => '<a class="text-primary-fixed hover:underline" href="#" onclick="event.preventDefault()">'.__('messages.privacy_policy').'</a>',
                ]) !!}
            </label>
        </div>

        <!-- Submit Button -->
        <button
            type="submit"
            class="w-full bg-primary-fixed-dim text-on-primary py-5 rounded-xl font-headline-md text-headline-md neon-btn-glow flex items-center justify-center gap-2 hover:brightness-110 active:scale-[0.98] transition-all duration-200"
        >
            {{ __('messages.create_my_account') }}
            <span class="material-symbols-outlined">arrow_forward</span>
        </button>
    </form>

    <!-- Login link -->
    <p class="text-center font-body-md text-on-surface-variant">
        <span>{{ __('messages.already_have_account') }}</span>
        <a class="text-primary-fixed hover:underline font-bold" href="{{ route('login') }}" wire:navigate>
            {{ __('messages.login') }}
        </a>
    </p>

    <script>
        function setAccountType(type) {
            const fanBtn = document.getElementById('fan-toggle');
            const artisteBtn = document.getElementById('artiste-toggle');
            const roleInput = document.getElementById('role-input');

            roleInput.value = type;

            if (type === 'fan') {
                fanBtn.classList.add('active-toggle');
                fanBtn.classList.remove('text-on-surface-variant');
                artisteBtn.classList.remove('active-toggle');
                artisteBtn.classList.add('text-on-surface-variant');
            } else {
                artisteBtn.classList.add('active-toggle');
                artisteBtn.classList.remove('text-on-surface-variant');
                fanBtn.classList.remove('active-toggle');
                fanBtn.classList.add('text-on-surface-variant');
            }
        }

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
