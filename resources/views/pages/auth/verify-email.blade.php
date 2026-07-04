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
                {{ __('messages.email_verification') }}
            </h1>
        </div>
    </div>

    <!-- Icon -->
    <div class="flex justify-center py-4">
        <div class="w-20 h-20 rounded-full bg-primary-fixed-dim/10 border border-primary-fixed-dim/30 flex items-center justify-center">
            <span class="material-symbols-outlined text-5xl text-primary-fixed-dim"
                  style="font-variation-settings: 'FILL' 1;">mark_email_unread</span>
        </div>
    </div>

    <div class="text-center space-y-4">
        <p class="font-body-md text-body-md text-on-surface-variant">
            {{ __('messages.verify_email_description') }}
        </p>

        @if (session('status') == 'verification-link-sent')
            <div class="font-body-md text-sm text-primary-fixed bg-primary-fixed/10 rounded-xl py-3 px-4 border border-primary-fixed/20">
                <span class="material-symbols-outlined text-sm align-text-bottom mr-1">check_circle</span>
                {{ __('messages.verification_link_sent') }}
            </div>
        @endif
    </div>

    <div class="space-y-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button
                type="submit"
                class="w-full bg-primary-fixed-dim text-on-primary py-5 rounded-xl font-headline-md text-headline-md neon-btn-glow flex items-center justify-center gap-2 hover:brightness-110 active:scale-[0.98] transition-all duration-200"
            >
                <span class="material-symbols-outlined">send</span>
                <span>{{ __('messages.resend_verification_email') }}</span>
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="w-full py-3 rounded-xl font-label-md text-label-md text-on-surface-variant border border-outline-variant hover:bg-surface-container transition-all flex items-center justify-center gap-2 cursor-pointer"
                data-test="logout-button"
            >
                <span class="material-symbols-outlined text-sm">logout</span>
                {{ __('messages.logout') }}
            </button>
        </form>
    </div>
</x-layouts::public-auth>
