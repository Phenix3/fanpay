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
                {{ __('messages.verify_your_phone') }}
            </h1>
            <p class="text-on-surface-variant font-body-md mt-2">
                {{ __('messages.verify_phone_description') }}
            </p>
        </div>
    </div>

    <!-- Session Status -->
    @if (session('status'))
        <div class="font-body-md text-sm text-primary-fixed text-center bg-primary-fixed/10 rounded-xl py-3 px-4 border border-primary-fixed/20">
            {{ session('status') }}
        </div>
    @endif

    <!-- Phone number display -->
    <div class="flex items-center justify-center gap-3 p-4 glass-card rounded-xl">
        <span class="material-symbols-outlined text-primary-fixed">smartphone</span>
        <span class="font-body-md text-body-md text-on-surface font-semibold">{{ session('otp_phone') }}</span>
    </div>

    <form method="POST" action="{{ route('otp.verify.submit') }}" class="space-y-6">
        @csrf

        <!-- OTP Code -->
        <div class="space-y-2 group">
            <label class="font-label-md text-label-md text-on-surface-variant ml-1" for="code">
                {{ __('messages.verification_code') }}
            </label>
            <div class="relative flex items-center glass-card rounded-xl glow-on-focus transition-all duration-300 @error('code') !border-error/50 !shadow-[0_0_15px_rgba(255,180,171,0.3)] @enderror">
                <span class="material-symbols-outlined absolute left-4 text-outline"
                      style="font-variation-settings: 'wght' 300;">pin</span>
                <input
                    class="w-full bg-transparent border-none py-4 pl-12 pr-4 text-on-surface focus:ring-0 placeholder:text-outline-variant font-body-md text-center text-2xl tracking-[0.3em]"
                    id="code"
                    name="code"
                    type="text"
                    required
                    autofocus
                    maxlength="6"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    placeholder="000000"
                />
            </div>
            @error('code')
                <p class="font-body-md text-sm text-error mt-1 ml-1">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="w-full bg-primary-fixed-dim text-on-primary py-5 rounded-xl font-headline-md text-headline-md neon-btn-glow flex items-center justify-center gap-2 hover:brightness-110 active:scale-[0.98] transition-all duration-200"
        >
            <span>{{ __('messages.verify') }}</span>
            <span class="material-symbols-outlined">check_circle</span>
        </button>
    </form>

    <!-- Resend Code -->
    <div class="text-center font-body-md text-on-surface-variant">
        <span>{{ __('messages.didnt_receive_code') }}</span>
        <form method="POST" action="{{ route('otp.send') }}" class="inline">
            @csrf
            <input type="hidden" name="phone" value="{{ session('otp_phone') }}">
            <input type="hidden" name="purpose" value="{{ session('otp_purpose', 'verification') }}">
            <button type="submit" class="text-primary-fixed hover:underline font-bold bg-transparent border-none cursor-pointer font-label-md text-label-md">
                {{ __('messages.resend_code') }}
            </button>
        </form>
    </div>
</x-layouts::public-auth>
