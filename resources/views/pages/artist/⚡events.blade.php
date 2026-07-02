<?php

use App\Domain\Events\Models\Event;
use App\Domain\Events\Models\TicketType;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Événements')] class extends Component {
    public bool $showForm = false;
    public ?int $editingEventId = null;

    public string $form_title = '';
    public string $form_venue = '';
    public string $form_address = '';
    public string $form_date = '';
    public string $form_description = '';
    public bool $form_is_published = false;

    // Ticket type management
    public ?int $managingEventId = null;
    public bool $showTicketForm = false;
    public ?int $editingTicketTypeId = null;
    public string $tt_name = '';
    public string $tt_price = '';
    public string $tt_quantity = '';
    public string $tt_description = '';

    protected function rules(): array
    {
        return [
            'form_title' => ['required', 'string', 'max:255'],
            'form_venue' => ['nullable', 'string', 'max:255'],
            'form_address' => ['nullable', 'string', 'max:500'],
            'form_date' => ['nullable', 'date'],
            'form_description' => ['nullable', 'string', 'max:5000'],
            'form_is_published' => ['boolean'],
        ];
    }

    protected function ticketTypeRules(): array
    {
        return [
            'tt_name' => ['required', 'string', 'max:255'],
            'tt_price' => ['required', 'integer', 'min:0', 'max:1000000'],
            'tt_quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'tt_description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    #[Computed]
    public function events()
    {
        return Event::where('artist_id', Auth::id())
            ->withCount('ticketTypes')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function managingEvent()
    {
        if (! $this->managingEventId) return null;
        return Event::with('ticketTypes')->find($this->managingEventId);
    }

    public function createEvent(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->editingEventId = null;
    }

    public function editEvent(int $id): void
    {
        $event = Event::where('artist_id', Auth::id())->findOrFail($id);
        $this->form_title = $event->title;
        $this->form_venue = $event->venue ?? '';
        $this->form_address = $event->address ?? '';
        $this->form_date = $event->date?->format('Y-m-d\TH:i') ?? '';
        $this->form_description = $event->description ?? '';
        $this->form_is_published = $event->is_published;
        $this->editingEventId = $event->id;
        $this->showForm = true;
    }

    public function saveEvent(): void
    {
        $data = $this->validate();

        $attributes = [
            'artist_id' => Auth::id(),
            'title' => $data['form_title'],
            'venue' => $data['form_venue'],
            'address' => $data['form_address'],
            'date' => $this->form_date ?: null,
            'description' => $data['form_description'],
            'is_published' => $data['form_is_published'],
        ];

        if ($this->editingEventId) {
            $event = Event::where('artist_id', Auth::id())->findOrFail($this->editingEventId);
            $event->update($attributes);
            Flux::toast(variant: 'success', text: __('Événement mis à jour.'));
        } else {
            $attributes['slug'] = Str::slug($data['form_title']).'-'.Str::random(8);
            Event::create($attributes);
            Flux::toast(variant: 'success', text: __('Événement créé avec succès.'));
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function manageTickets(int $eventId): void
    {
        $this->managingEventId = $eventId;
        $this->showTicketForm = false;
    }

    public function addTicketType(): void
    {
        $this->resetTicketForm();
        $this->showTicketForm = true;
        $this->editingTicketTypeId = null;
    }

    public function editTicketType(int $id): void
    {
        $tt = TicketType::where('event_id', $this->managingEventId)->findOrFail($id);
        $this->tt_name = $tt->name;
        $this->tt_price = (string) $tt->price;
        $this->tt_quantity = (string) $tt->quantity;
        $this->tt_description = $tt->description ?? '';
        $this->editingTicketTypeId = $tt->id;
        $this->showTicketForm = true;
    }

    public function saveTicketType(): void
    {
        $data = $this->validate($this->ticketTypeRules());

        $attributes = [
            'event_id' => $this->managingEventId,
            'name' => $data['tt_name'],
            'price' => (int) $this->tt_price,
            'quantity' => (int) $this->tt_quantity,
            'description' => $data['tt_description'],
        ];

        if ($this->editingTicketTypeId) {
            TicketType::where('event_id', $this->managingEventId)->findOrFail($this->editingTicketTypeId)->update($attributes);
            Flux::toast(variant: 'success', text: __('Type de billet mis à jour.'));
        } else {
            TicketType::create($attributes);
            Flux::toast(variant: 'success', text: __('Type de billet ajouté.'));
        }

        $this->resetTicketForm();
        $this->showTicketForm = false;
    }

    public function deleteTicketType(int $id): void
    {
        TicketType::where('event_id', $this->managingEventId)->findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: __('Type de billet supprimé.'));
    }

    public function closeManageTickets(): void
    {
        $this->managingEventId = null;
        $this->showTicketForm = false;
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    public function cancelTicketForm(): void
    {
        $this->resetTicketForm();
        $this->showTicketForm = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'form_title', 'form_venue', 'form_address', 'form_date',
            'form_description', 'form_is_published', 'editingEventId',
        ]);
    }

    private function resetTicketForm(): void
    {
        $this->reset([
            'tt_name', 'tt_price', 'tt_quantity', 'tt_description', 'editingTicketTypeId',
        ]);
    }

    public function eventStatus(Event $event): string
    {
        if (! $event->date) return 'draft';
        if ($event->date->isPast()) return 'past';
        return 'upcoming';
    }

    public function eventStatusLabel(Event $event): string
    {
        return match ($this->eventStatus($event)) {
            'draft' => __('Brouillon'),
            'past' => __('Passé'),
            'upcoming' => __('À venir'),
            default => '',
        };
    }

    public function eventStatusColor(Event $event): string
    {
        return match ($this->eventStatus($event)) {
            'draft' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-400',
            'past' => 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
            'upcoming' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
            default => '',
        };
    }
}; ?>

<div class="space-y-8">
    {{-- En-tête --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Événements') }}</flux:heading>
            <flux:subheading>{{ __('Créez et gérez vos événements et billetterie') }}</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="createEvent">
            <flux:icon.plus class="size-4" />
            {{ __('Nouvel événement') }}
        </flux:button>
    </div>

    {{-- Formulaire événement --}}
    @if ($showForm)
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <form wire:submit="saveEvent" class="space-y-5 max-w-2xl">
                <flux:input wire:model="form_title" :label="__('Titre de l\'événement')" required placeholder="{{ __('ex: Concert Live à Douala') }}" />
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <flux:input wire:model="form_venue" :label="__('Lieu')" placeholder="{{ __('ex: Palais des Sports') }}" />
                    <flux:input wire:model="form_date" :label="__('Date & heure')" type="datetime-local" />
                </div>
                <flux:input wire:model="form_address" :label="__('Adresse')" placeholder="{{ __('Adresse complète...') }}" />
                <flux:textarea wire:model="form_description" :label="__('Description')" rows="3" />
                <div class="flex items-center gap-3">
                    <flux:switch wire:model="form_is_published" :label="__('Publier')" />
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <flux:button variant="primary" type="submit">{{ $editingEventId ? __('Enregistrer') : __('Créer l\'événement') }}</flux:button>
                    <flux:button variant="ghost" wire:click="cancelForm">{{ __('Annuler') }}</flux:button>
                </div>
            </form>
        </div>
    @endif

    {{-- Gestion des billets --}}
    @if ($managingEventId && $this->managingEvent)
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <flux:button variant="ghost" wire:click="closeManageTickets" icon="arrow-left" size="sm" />
                    <flux:heading size="sm">{{ $this->managingEvent->title }}</flux:heading>
                </div>
                <flux:button variant="primary" size="sm" wire:click="addTicketType">
                    <flux:icon.plus class="size-4" /> {{ __('Ajouter un type de billet') }}
                </flux:button>
            </div>

            {{-- Formulaire billet --}}
            @if ($showTicketForm)
                <div class="mb-4 rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                    <form wire:submit="saveTicketType" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                            <div class="sm:col-span-2">
                                <flux:input wire:model="tt_name" :label="__('Nom')" required placeholder="{{ __('ex: Standard, VIP, Gold') }}" />
                            </div>
                            <flux:input wire:model="tt_price" :label="__('Prix (FCFA)')" type="number" min="0" required />
                            <flux:input wire:model="tt_quantity" :label="__('Quantité')" type="number" min="1" required />
                        </div>
                        <flux:input wire:model="tt_description" :label="__('Description')" placeholder="{{ __('Avantages inclus...') }}" />
                        <div class="flex gap-2">
                            <flux:button variant="primary" type="submit" size="sm">{{ $editingTicketTypeId ? __('Enregistrer') : __('Ajouter') }}</flux:button>
                            <flux:button variant="ghost" wire:click="cancelTicketForm" size="sm">{{ __('Annuler') }}</flux:button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- Liste des types de billets --}}
            <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->managingEvent->ticketTypes as $tt)
                    <div class="flex items-center justify-between py-3">
                        <div class="flex items-center gap-3">
                            <flux:icon.ticket class="size-5 text-zinc-400" />
                            <div>
                                <p class="font-medium text-sm text-zinc-900 dark:text-white">{{ $tt->name }}</p>
                                <p class="text-xs text-zinc-500">{{ number_format($tt->price) }} FCFA · {{ $tt->sold }}/{{ $tt->quantity }} {{ __('vendus') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="editTicketType({{ $tt->id }})" />
                            <flux:button variant="ghost" size="sm" icon="trash" wire:click="deleteTicketType({{ $tt->id }})" wire:confirm="{{ __('Supprimer ce type de billet ?') }}" class="text-red-500" />
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-zinc-500">{{ __('Aucun type de billet. Ajoutez des catégories de billets.') }}</p>
                @endforelse
            </div>
        </div>
    @endif

    {{-- Liste des événements --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->events as $event)
            <div class="group rounded-2xl border border-zinc-200 bg-white transition-all hover:shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-3">
                        <flux:heading size="sm" class="!leading-tight">{{ $event->title }}</flux:heading>
                        <span class="shrink-0 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $this->eventStatusColor($event) }}">
                            {{ $this->eventStatusLabel($event) }}
                        </span>
                    </div>

                    <div class="space-y-2 text-sm text-zinc-500 mb-4">
                        @if ($event->venue)
                            <div class="flex items-center gap-2">
                                <flux:icon.map-pin class="size-4 shrink-0" />
                                <span>{{ $event->venue }}</span>
                            </div>
                        @endif
                        @if ($event->date)
                            <div class="flex items-center gap-2">
                                <flux:icon.calendar class="size-4 shrink-0" />
                                <span>{{ $event->date->translatedFormat('d M Y H:i') }}</span>
                            </div>
                        @endif
                        <div class="flex items-center gap-2">
                            <flux:icon.ticket class="size-4 shrink-0" />
                            <span>{{ $event->ticket_types_count }} {{ __('type(s) de billet') }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                        <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="editEvent({{ $event->id }})" />
                        <flux:button variant="ghost" size="sm" icon="ticket" wire:click="manageTickets({{ $event->id }})" :title="__('Billets')" />
                        <flux:spacer />
                        <flux:badge size="sm" color="{{ $event->is_published ? 'emerald' : 'zinc' }}">
                            {{ $event->is_published ? __('Publié') : __('Brouillon') }}
                        </flux:badge>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full p-16 text-center">
                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon.calendar class="size-8 text-zinc-400" />
                </div>
                <p class="font-medium text-zinc-900 dark:text-white">{{ __('Aucun événement') }}</p>
                <flux:text class="mt-1">{{ __('Créez votre premier événement et vendez des billets.') }}</flux:text>
                <flux:button variant="primary" wire:click="createEvent" class="mt-4">{{ __('Créer un événement') }}</flux:button>
            </div>
        @endforelse
    </div>
</div>
