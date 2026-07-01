<x-layouts::auth :title="__('Verify your phone')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Verify your phone number')"
            :description="__('Enter the 6-digit code sent to your phone')"
        />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('otp.verify.submit') }}" class="flex flex-col gap-6">
            @csrf

            <div class="text-center">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('A code has been sent to') }}
                    <strong>{{ session('otp_phone') }}</strong>
                </p>
            </div>

            <!-- OTP Code -->
            <flux:input
                name="code"
                :label="__('Verification code')"
                type="text"
                required
                autofocus
                maxlength="6"
                inputmode="numeric"
                pattern="[0-9]{6}"
                :placeholder="__('000000')"
                class="text-center text-2xl tracking-widest"
            />

            <flux:button type="submit" variant="primary" class="w-full">
                {{ __('Verify') }}
            </flux:button>
        </form>

        <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Didn\\'t receive the code?') }}</span>
            <form method="POST" action="{{ route('otp.send') }}" class="inline">
                @csrf
                <input type="hidden" name="phone" value="{{ session('otp_phone') }}">
                <input type="hidden" name="purpose" value="{{ session('otp_purpose', 'verification') }}">
                <flux:link as="button" type="submit" class="cursor-pointer">
                    {{ __('Resend code') }}
                </flux:link>
            </form>
        </div>
    </div>
</x-layouts::auth>
