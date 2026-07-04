@php $title = $event->title . ' - ' . config('app.name'); @endphp

<x-layouts::public>
    <!-- Atmospheric Background -->
    <div class="fixed inset-0 z-[-1] bg-radial-gradient pointer-events-none"></div>

    <div class="flex-grow px-margin-mobile md:px-margin-desktop pb-32 md:pb-24 max-w-container-max mx-auto w-full">

        {{-- Hero Section --}}
        <section class="relative w-full min-h-[40vh] md:min-h-[55vh] rounded-3xl overflow-hidden mt-24 md:mt-32 mb-8 md:mb-12 group">
            <!-- Cover Image -->
            <div class="absolute inset-0 bg-cover bg-center transition-transform duration-700 group-hover:scale-105"
                 style="background-image: url('{{ $event->cover ?? 'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=1400&q=80' }}');">
            </div>
            <!-- Gradient Overlays -->
            <div class="absolute inset-0 bg-gradient-to-t from-background via-background/50 to-transparent z-10"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-background/40 to-transparent z-10"></div>

            <!-- Content -->
            <div class="absolute bottom-0 left-0 right-0 z-20 p-6 md:p-10 lg:p-14">
                <!-- Badge -->
                @php
                    $isUpcoming = $event->date && $event->date->isFuture();
                    $isToday = $event->date && $event->date->isToday();
                @endphp
                @if($isToday)
                    <span class="inline-block px-4 py-1.5 rounded-full bg-primary-container text-on-primary-container font-label-md text-label-md uppercase tracking-wider mb-4 shadow-[0_0_15px_rgba(0,255,157,0.4)]">
                        {{ __('messages.event_today') }}
                    </span>
                @elseif($isUpcoming)
                    <span class="inline-block px-4 py-1.5 rounded-full bg-secondary-container text-on-secondary-container font-label-md text-label-md uppercase tracking-wider mb-4 shadow-[0_0_15px_rgba(255,86,44,0.4)]">
                        {{ __('messages.event_upcoming') }}
                    </span>
                @else
                    <span class="inline-block px-4 py-1.5 rounded-full bg-surface-variant text-on-surface-variant font-label-md text-label-md uppercase tracking-wider mb-4">
                        {{ __('messages.event_past') }}
                    </span>
                @endif

                <h1 class="font-display-lg text-display-lg text-primary leading-tight mb-3">
                    {{ $event->title }}
                </h1>

                <div class="flex flex-wrap gap-x-8 gap-y-3 items-center text-on-surface-variant font-body-md text-body-md">
                    @if($event->artist)
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary-fixed-dim text-xl">mic</span>
                            <span>{{ $event->artist->artistProfile->stage_name ?? $event->artist->name }}</span>
                        </div>
                    @endif
                    @if($event->date)
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary-fixed-dim text-xl">calendar_month</span>
                            <span>{{ $event->date->isoFormat('D MMMM YYYY') }} à {{ $event->date->format('H:i') }}</span>
                        </div>
                    @endif
                    @if($event->venue)
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary-fixed-dim text-xl">location_on</span>
                            <span>{{ $event->venue }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- Content Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 md:gap-12">

            {{-- Left Column: Event Details --}}
            <div class="lg:col-span-2 space-y-8">
                {{-- Description --}}
                @if($event->description)
                    <section>
                        <h2 class="font-headline-md text-headline-md text-on-surface mb-4">
                            {{ __('messages.about_event') }}
                        </h2>
                        <div class="font-body-md text-body-md text-on-surface-variant leading-relaxed whitespace-pre-line">
                            {{ $event->description }}
                        </div>
                    </section>
                @endif

                {{-- Address / Location --}}
                @if($event->address)
                    <section>
                        <h2 class="font-headline-md text-headline-md text-on-surface mb-4">
                            {{ __('messages.location') }}
                        </h2>
                        <div class="glass-panel rounded-xl p-6 flex items-start gap-4">
                            <span class="material-symbols-outlined text-primary-fixed-dim text-3xl shrink-0">map</span>
                            <div>
                                <p class="font-body-md text-body-md text-on-surface font-semibold">{{ $event->venue }}</p>
                                <p class="font-body-md text-body-md text-on-surface-variant">{{ $event->address }}</p>
                            </div>
                        </div>
                    </section>
                @endif
            </div>

            {{-- Right Column: Ticket Types --}}
            <div class="lg:col-span-1">
                <div class="lg:sticky lg:top-28 space-y-6">
                    <h2 class="font-headline-md text-headline-md text-on-surface">
                        {{ __('messages.tickets') }}
                    </h2>

                    @if($event->ticketTypes && $event->ticketTypes->count() > 0)
                        @foreach($event->ticketTypes as $ticketType)
                            <div class="glass-panel rounded-xl p-6 space-y-4 transition-all duration-300 hover:border-primary-fixed-dim/30 glow-border">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-headline-md text-headline-md text-on-surface">
                                            {{ $ticketType->name }}
                                        </h3>
                                        @if($ticketType->description)
                                            <p class="font-body-md text-body-md text-on-surface-variant mt-1">
                                                {{ $ticketType->description }}
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-end justify-between pt-2 border-t border-outline-variant/20">
                                    <div>
                                        <span class="font-display-lg text-display-lg text-primary-fixed-dim font-bold">
                                            {{ number_format($ticketType->price, 0, ',', ' ') }}
                                        </span>
                                        <span class="font-label-md text-label-md text-on-surface-variant"> FCFA</span>
                                    </div>
                                    <div class="text-right">
                                        @if($ticketType->hasAvailableTickets())
                                            <button type="button"
                                                class="bg-primary-fixed-dim text-on-primary px-5 py-3 rounded-xl font-label-md text-label-md font-bold hover:brightness-110 active:scale-[0.98] transition-all flex items-center gap-2 neon-btn-glow">
                                                <span class="material-symbols-outlined text-sm">confirmation_number</span>
                                                {{ __('messages.buy_ticket') }}
                                            </button>
                                            @if($ticketType->quantity > 0)
                                                <p class="font-label-md text-label-md text-on-surface-variant mt-2 text-xs">
                                                    {{ $ticketType->quantity - ($ticketType->sold ?? 0) }} / {{ $ticketType->quantity }} {{ __('messages.available') }}
                                                </p>
                                            @endif
                                        @else
                                            <span class="inline-block px-4 py-2 rounded-xl bg-error/10 text-error font-label-md text-label-md font-bold border border-error/30">
                                                {{ __('messages.sold_out') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="glass-panel rounded-xl p-8 text-center">
                            <span class="material-symbols-outlined text-4xl text-on-surface-variant mb-3">confirmation_number</span>
                            <p class="font-body-md text-body-md text-on-surface-variant">
                                {{ __('messages.no_tickets_available') }}
                            </p>
                        </div>
                    @endif

                    {{-- Back to Explore --}}
                    <a href="{{ route('boutique') }}"
                       class="w-full inline-flex items-center justify-center gap-2 py-4 rounded-xl font-label-md text-label-md text-on-surface-variant border border-outline-variant hover:bg-surface-container transition-all"
                       wire:navigate>
                        <span class="material-symbols-outlined text-sm">arrow_back</span>
                        {{ __('messages.back_to_explore') }}
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-layouts::public>
