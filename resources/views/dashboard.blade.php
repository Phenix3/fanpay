<x-layouts::app :title="__('messages.dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        {{-- Welcome header --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">{{ __('messages.welcome') }}, {{ auth()->user()->name }}!</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    @switch(auth()->user()->role->value)
                        @case('admin')
                            {{ __('Plateforme de supervision') }}
                            @break
                        @case('artist')
                            {{ __('Gérez votre musique et vos revenus') }}
                            @break
                        @default
                            {{ __('Découvrez et soutenez vos artistes préférés') }}
                    @endswitch
                </p>
            </div>
        </div>

        {{-- Stats cards --}}
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
            <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
        </div>
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
        </div>
    </div>
</x-layouts::app>
