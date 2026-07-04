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
    </div>

    <div
        class="w-full"
        x-cloak
        x-data="{
            showRecoveryInput: @js($errors->has('recovery_code')),
            code: '',
            recovery_code: '',
            focusOtp() {
                this.$nextTick(() => this.$refs.otpInput?.focus());
            },
            init() {
                if (! this.showRecoveryInput) {
                    this.focusOtp();
                }
            },
            toggleInput() {
                this.showRecoveryInput = !this.showRecoveryInput;
                this.code = '';
                this.recovery_code = '';
                this.$nextTick(() => {
                    this.showRecoveryInput
                        ? this.$refs.recoveryInput?.focus()
                        : this.focusOtp();
                });
            },
        }"
    >
        <!-- OTP / Authentication Code Mode -->
        <div x-show="!showRecoveryInput" class="space-y-6">
            <div class="text-center md:text-left">
                <h1 class="font-headline-lg text-headline-lg text-on-surface">
                    {{ __('messages.authentication_code') }}
                </h1>
                <p class="text-on-surface-variant font-body-md mt-2">
                    {{ __('messages.auth_code_description') }}
                </p>
            </div>

            <form method="POST" action="{{ route('two-factor.login.store') }}" class="space-y-6">
                @csrf

                <div class="flex justify-center">
                    <div class="relative glass-card rounded-xl glow-on-focus transition-all duration-300 w-full max-w-[280px]">
                        <input
                            x-ref="otpInput"
                            x-model="code"
                            class="w-full bg-transparent border-none py-6 text-center text-3xl tracking-[0.5em] text-on-surface focus:ring-0 placeholder:text-outline-variant font-body-md"
                            type="text"
                            name="code"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            pattern="[0-9]{6}"
                            maxlength="6"
                            placeholder="000000"
                        />
                    </div>
                </div>
                @error('code')
                    <p class="font-body-md text-sm text-error text-center">{{ $message }}</p>
                @enderror

                <button
                    type="submit"
                    class="w-full bg-primary-fixed-dim text-on-primary py-5 rounded-xl font-headline-md text-headline-md neon-btn-glow flex items-center justify-center gap-2 hover:brightness-110 active:scale-[0.98] transition-all duration-200"
                >
                    <span>{{ __('messages.continue_btn') }}</span>
                    <span class="material-symbols-outlined">arrow_forward</span>
                </button>
            </form>

            <p class="text-center font-body-md text-on-surface-variant">
                <span class="text-on-surface-variant/60">{{ __('messages.or_you_can') }}</span>
                <button type="button" @click="toggleInput()"
                    class="text-primary-fixed hover:underline font-bold bg-transparent border-none cursor-pointer font-label-md text-label-md">
                    {{ __('messages.login_using_recovery_code') }}
                </button>
            </p>
        </div>

        <!-- Recovery Code Mode -->
        <div x-show="showRecoveryInput" class="space-y-6">
            <div class="text-center md:text-left">
                <h1 class="font-headline-lg text-headline-lg text-on-surface">
                    {{ __('messages.recovery_code') }}
                </h1>
                <p class="text-on-surface-variant font-body-md mt-2">
                    {{ __('messages.recovery_code_description') }}
                </p>
            </div>

            <form method="POST" action="{{ route('two-factor.login.store') }}" class="space-y-6">
                @csrf

                <div class="space-y-2 group">
                    <div class="relative flex items-center glass-card rounded-xl glow-on-focus transition-all duration-300 @error('recovery_code') !border-error/50 !shadow-[0_0_15px_rgba(255,180,171,0.3)] @enderror">
                        <span class="material-symbols-outlined absolute left-4 text-outline"
                              style="font-variation-settings: 'wght' 300;">key</span>
                        <input
                            x-ref="recoveryInput"
                            x-model="recovery_code"
                            class="w-full bg-transparent border-none py-4 pl-12 pr-4 text-on-surface focus:ring-0 placeholder:text-outline-variant font-body-md"
                            type="text"
                            name="recovery_code"
                            x-bind:required="showRecoveryInput"
                            autocomplete="one-time-code"
                            placeholder="XXXXX-XXXXX"
                        />
                    </div>
                    @error('recovery_code')
                        <p class="font-body-md text-sm text-error mt-1 ml-1">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="w-full bg-primary-fixed-dim text-on-primary py-5 rounded-xl font-headline-md text-headline-md neon-btn-glow flex items-center justify-center gap-2 hover:brightness-110 active:scale-[0.98] transition-all duration-200"
                >
                    <span>{{ __('messages.continue_btn') }}</span>
                    <span class="material-symbols-outlined">arrow_forward</span>
                </button>
            </form>

            <p class="text-center font-body-md text-on-surface-variant">
                <span class="text-on-surface-variant/60">{{ __('messages.or_you_can') }}</span>
                <button type="button" @click="toggleInput()"
                    class="text-primary-fixed hover:underline font-bold bg-transparent border-none cursor-pointer font-label-md text-label-md">
                    {{ __('messages.login_using_auth_code') }}
                </button>
            </p>
        </div>
    </div>
</x-layouts::public-auth>
