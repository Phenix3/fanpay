<x-layouts::auth :title="__('Create an account')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Name -->
            <flux:input
                name="name"
                :label="__('messages.name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('messages.email')"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Phone -->
            <flux:input
                name="phone"
                :label="__('messages.phone')"
                :value="old('phone')"
                type="tel"
                required
                autocomplete="tel"
                placeholder="691234567"
                inputmode="numeric"
                pattern="[0-9]{9}"
            />

            <!-- Role -->
            <fieldset class="space-y-2">
                <legend class="text-sm font-medium text-zinc-800 dark:text-zinc-200">
                    {{ __('Je suis...') }}
                </legend>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <flux:radio name="role" value="fan" checked />
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('messages.fan') }}</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <flux:radio name="role" value="artist" />
                        <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('messages.artist') }}</span>
                    </label>
                </div>
            </fieldset>

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('messages.password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('messages.password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
