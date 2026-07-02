<?php

use App\Domain\Vip\Models\VipPlan;
use App\Domain\Vip\Models\VipSubscription;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Abonnements VIP')] class extends Component {
    public bool $showForm = false;
    public ?int $editingPlanId = null;

    public string $form_name = '';
    public string $form_duration_days = '30';
    public string $form_price = '';
    public string $form_perks = '';
    public bool $form_is_active = true;

    protected function rules(): array
    {
        return [
            'form_name' => ['required', 'string', 'max:255'],
            'form_duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'form_price' => ['required', 'integer', 'min:0', 'max:1000000'],
            'form_perks' => ['nullable', 'string', 'max:2000'],
            'form_is_active' => ['boolean'],
        ];
    }

    #[Computed]
    public function plans()
    {
        return VipPlan::where('artist_id', Auth::id())
            ->withCount(['subscriptions' => fn ($q) => $q->where('status', 'active')->where('expires_at', '>', now())])
            ->orderBy('price')
            ->orderBy('duration_days')
            ->get();
    }

    #[Computed]
    public function activeSubscribersCount(): int
    {
        return VipSubscription::whereIn('plan_id', VipPlan::where('artist_id', Auth::id())->pluck('id'))
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->count();
    }

    #[Computed]
    public function monthlyRevenue(): int
    {
        // Estimate: sum of all active subscription prices / duration in months
        $activeSubs = VipSubscription::whereIn('plan_id', VipPlan::where('artist_id', Auth::id())->pluck('id'))
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->with('plan')
            ->get();

        return $activeSubs->sum(fn ($sub) => $sub->plan->price);
    }

    public function createPlan(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->editingPlanId = null;
    }

    public function editPlan(int $id): void
    {
        $plan = VipPlan::where('artist_id', Auth::id())->findOrFail($id);
        $this->form_name = $plan->name;
        $this->form_duration_days = (string) $plan->duration_days;
        $this->form_price = (string) $plan->price;
        $this->form_perks = is_array($plan->perks) ? implode("\n", $plan->perks) : '';
        $this->form_is_active = $plan->is_active;
        $this->editingPlanId = $plan->id;
        $this->showForm = true;
    }

    public function savePlan(): void
    {
        $data = $this->validate();

        // Convert perks from textarea lines to array
        $perks = array_filter(
            explode("\n", str_replace("\r\n", "\n", $data['form_perks'])),
            fn ($line) => trim($line) !== ''
        );

        $attributes = [
            'artist_id' => Auth::id(),
            'name' => $data['form_name'],
            'duration_days' => (int) $this->form_duration_days,
            'price' => (int) $this->form_price,
            'perks' => $perks,
            'is_active' => $data['form_is_active'],
        ];

        if ($this->editingPlanId) {
            VipPlan::where('artist_id', Auth::id())->findOrFail($this->editingPlanId)->update($attributes);
            Flux::toast(variant: 'success', text: __('Plan VIP mis à jour.'));
        } else {
            VipPlan::create($attributes);
            Flux::toast(variant: 'success', text: __('Plan VIP créé avec succès.'));
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function toggleActive(int $id): void
    {
        $plan = VipPlan::where('artist_id', Auth::id())->findOrFail($id);
        $plan->update(['is_active' => !$plan->is_active]);
        Flux::toast(variant: 'success', text: $plan->is_active ? __('Plan activé.') : __('Plan désactivé.'));
    }

    public function deletePlan(int $id): void
    {
        $plan = VipPlan::where('artist_id', Auth::id())->findOrFail($id);

        if ($plan->subscriptions()->where('status', 'active')->exists()) {
            Flux::toast(variant: 'danger', text: __('Impossible de supprimer un plan avec des abonnés actifs.'));
            return;
        }

        $plan->delete();
        Flux::toast(variant: 'success', text: __('Plan supprimé.'));
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'form_name', 'form_duration_days', 'form_price',
            'form_perks', 'form_is_active', 'editingPlanId',
        ]);
        $this->form_duration_days = '30';
        $this->form_is_active = true;
    }

    public function planDurationLabel(int $days): string
    {
        return $days < 30
            ? __(':days jours', ['days' => $days])
            : __(':months mois', ['months' => intdiv($days, 30)]);
    }


}; ?>

<div class="space-y-8">
    {{-- En-tête --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Abonnements VIP') }}</flux:heading>
            <flux:subheading>{{ __('Créez des plans d\'abonnement pour vos fans') }}</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="createPlan">
            <flux:icon.plus class="size-4" />
            {{ __('Nouveau plan') }}
        </flux:button>
    </div>

    {{-- Statistiques --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
        <div class="rounded-2xl border border-zinc-200 bg-gradient-to-br from-amber-50 to-white p-6 dark:border-zinc-700 dark:from-amber-950/20 dark:to-zinc-900">
            <div class="flex items-center justify-between">
                <flux:icon.crown class="size-5 text-amber-500" />
                <span class="text-xs font-medium text-zinc-400 uppercase tracking-wider">{{ __('Plans actifs') }}</span>
            </div>
            <p class="mt-4 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                {{ $this->plans->where('is_active', true)->count() }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:icon.users class="size-5 text-blue-500" />
                <span class="text-xs font-medium text-zinc-400 uppercase tracking-wider">{{ __('Abonnés actifs') }}</span>
            </div>
            <p class="mt-4 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                {{ $this->activeSubscribersCount }}
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:icon.arrow-trending-up class="size-5 text-emerald-500" />
                <span class="text-xs font-medium text-zinc-400 uppercase tracking-wider">{{ __('Revenu mensuel estimé') }}</span>
            </div>
            <p class="mt-4 text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">
                {{ number_format($this->monthlyRevenue) }} <span class="text-lg font-medium text-zinc-500">FCFA</span>
            </p>
        </div>
    </div>

    {{-- Formulaire --}}
    @if ($showForm)
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/30">
                    <flux:icon.crown class="size-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <flux:heading>{{ $editingPlanId ? __('Modifier le plan') : __('Nouveau plan VIP') }}</flux:heading>
                    <flux:subheading>{{ $editingPlanId ? __('Modifiez les avantages et le prix de votre plan') : __('Créez un abonnement avec des avantages exclusifs pour vos fans') }}</flux:subheading>
                </div>
            </div>

            <form wire:submit="savePlan" class="space-y-5 max-w-2xl">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <flux:input wire:model="form_name" :label="__('Nom du plan')" required placeholder="{{ __('ex: Fan Premium, Super Fan, etc.') }}" />
                    </div>

                    <flux:input wire:model="form_price" :label="__('Prix (FCFA/mois)')" type="number" min="0" step="500" required placeholder="5000" />
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <flux:select wire:model="form_duration_days" :label="__('Durée')">
                        <option value="30">{{ __('1 mois') }}</option>
                        <option value="90">{{ __('3 mois') }}</option>
                        <option value="180">{{ __('6 mois') }}</option>
                        <option value="365">{{ __('1 an') }}</option>
                    </flux:select>

                    <div class="flex items-end pb-2">
                        <flux:switch wire:model="form_is_active" :label="__('Plan actif')" description="{{ __('Les plans inactifs ne sont plus proposés aux fans') }}" />
                    </div>
                </div>

                <div>
                    <flux:textarea
                        wire:model="form_perks"
                        :label="__('Avantages exclusifs')"
                        rows="5"
                        placeholder="{{ __('Accès aux morceaux exclusifs\nContenu behind-the-scenes\nBadge VIP sur le profil\nMessages prioritaires\n...') }}"
                        description="{{ __('Un avantage par ligne') }}"
                    />
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <flux:button variant="primary" type="submit">
                        {{ $editingPlanId ? __('Enregistrer') : __('Créer le plan') }}
                    </flux:button>
                    <flux:button variant="ghost" wire:click="cancelForm">
                        {{ __('Annuler') }}
                    </flux:button>
                </div>
            </form>
        </div>
    @endif

    {{-- Liste des plans --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->plans as $plan)
            <div class="group rounded-2xl border border-zinc-200 bg-white transition-all hover:shadow-lg dark:border-zinc-700 dark:bg-zinc-900 {{ !$plan->is_active ? 'opacity-70' : '' }}">
                {{-- Header --}}
                <div class="rounded-t-2xl bg-gradient-to-r from-amber-500 to-amber-600 p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <flux:icon.crown class="size-5" />
                            <flux:heading class="!text-white !text-lg">{{ $plan->name }}</flux:heading>
                        </div>
                        @if (!$plan->is_active)
                            <span class="inline-flex items-center rounded-full bg-white/20 px-2.5 py-0.5 text-xs font-medium backdrop-blur-sm">
                                {{ __('Inactif') }}
                            </span>
                        @endif
                    </div>
                    <div class="mt-4">
                        <span class="text-3xl font-bold">{{ number_format($plan->price) }}</span>
                        <span class="text-sm opacity-80"> FCFA</span>
                        <span class="text-sm opacity-80"> / {{ $this->planDurationLabel($plan->duration_days) }}</span>
                    </div>
                </div>

                {{-- Body --}}
                <div class="p-6 space-y-4">
                    {{-- Abonnés --}}
                    <div class="flex items-center gap-2 text-sm text-zinc-500">
                        <flux:icon.users class="size-4" />
                        <span>{{ $plan->subscriptions_count }} {{ __('abonnés') }}</span>
                        <span class="mx-1 opacity-50">·</span>
                        <flux:icon.clock class="size-4" />
                        <span>{{ $this->planDurationLabel($plan->duration_days) }}</span>
                    </div>

                    {{-- Perks --}}
                    @if ($plan->perks)
                        <ul class="space-y-2">
                            @foreach ($plan->perks as $perk)
                                <li class="flex items-start gap-2 text-sm">
                                    <flux:icon.check class="size-4 text-emerald-500 mt-0.5 shrink-0" />
                                    <span class="text-zinc-700 dark:text-zinc-300">{{ $perk }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                        <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="editPlan({{ $plan->id }})" :title="__('Modifier')" />
                        <flux:button variant="ghost" size="sm" :icon="$plan->is_active ? 'pause' : 'play'" wire:click="toggleActive({{ $plan->id }})" :title="$plan->is_active ? __('Désactiver') : __('Activer')" />
                        <flux:spacer />
                        <flux:button
                            variant="danger"
                            size="sm"
                            icon="trash"
                            wire:click="deletePlan({{ $plan->id }})"
                            wire:confirm="{{ __('Supprimer ce plan ? Les abonnés actifs seront conservés.') }}"
                            :title="__('Supprimer')"
                            class="hover:bg-red-50 dark:hover:bg-red-950/50"
                        />
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full p-16 text-center">
                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-amber-100 dark:bg-amber-900/30">
                    <flux:icon.crown class="size-8 text-amber-500" />
                </div>
                <p class="font-medium text-zinc-900 dark:text-white">{{ __('Aucun plan VIP') }}</p>
                <flux:text class="mt-1">{{ __('Créez votre premier plan d\'abonnement pour offrir des avantages exclusifs à vos fans.') }}</flux:text>
                <flux:button variant="primary" wire:click="createPlan" class="mt-4">
                    {{ __('Créer mon premier plan') }}
                </flux:button>
            </div>
        @endforelse
    </div>
</div>
