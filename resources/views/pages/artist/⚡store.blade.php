<?php

use App\Domain\Store\Models\Product;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Ma boutique')] class extends Component {
    use WithPagination;

    public bool $showForm = false;
    public ?int $editingProductId = null;

    public string $form_name = '';
    public string $form_type = 'digital';
    public string $form_price = '';
    public string $form_stock = '';
    public string $form_description = '';
    public bool $form_is_published = false;

    public string $search = '';
    public string $filter_type = '';
    public string $filter_status = '';

    protected function rules(): array
    {
        return [
            'form_name' => ['required', 'string', 'max:255'],
            'form_type' => ['required', 'in:digital,physical,merch'],
            'form_price' => ['required', 'integer', 'min:0', 'max:1000000'],
            'form_stock' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'form_description' => ['nullable', 'string', 'max:5000'],
            'form_is_published' => ['boolean'],
        ];
    }

    #[Computed]
    public function products()
    {
        return Product::where('artist_id', Auth::id())
            ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->when($this->filter_type, fn ($q) => $q->where('type', $this->filter_type))
            ->when($this->filter_status !== '', fn ($q) => $q->where('is_published', $this->filter_status === 'published'))
            ->orderBy('created_at', 'desc')
            ->paginate(12);
    }

    public function createProduct(): void
    {
        $this->resetForm();
        $this->showForm = true;
        $this->editingProductId = null;
    }

    public function editProduct(int $id): void
    {
        $product = Product::where('artist_id', Auth::id())->findOrFail($id);
        $this->form_name = $product->name;
        $this->form_type = $product->type;
        $this->form_price = (string) $product->price;
        $this->form_stock = $product->stock !== null ? (string) $product->stock : '';
        $this->form_description = $product->description ?? '';
        $this->form_is_published = $product->is_published;
        $this->editingProductId = $product->id;
        $this->showForm = true;
    }

    public function saveProduct(): void
    {
        $data = $this->validate();

        $attributes = [
            'artist_id' => Auth::id(),
            'name' => $data['form_name'],
            'type' => $data['form_type'],
            'price' => (int) $this->form_price,
            'stock' => $this->form_stock !== '' ? (int) $this->form_stock : null,
            'description' => $data['form_description'],
            'is_published' => $data['form_is_published'],
        ];

        if ($this->editingProductId) {
            Product::where('artist_id', Auth::id())->findOrFail($this->editingProductId)->update($attributes);
            Flux::toast(variant: 'success', text: __('Produit mis à jour.'));
        } else {
            Product::create($attributes);
            Flux::toast(variant: 'success', text: __('Produit créé avec succès.'));
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function togglePublish(int $id): void
    {
        $product = Product::where('artist_id', Auth::id())->findOrFail($id);
        $product->update(['is_published' => !$product->is_published]);
        Flux::toast(variant: 'success', text: $product->is_published ? __('Produit publié.') : __('Produit masqué.'));
    }

    public function deleteProduct(int $id): void
    {
        Product::where('artist_id', Auth::id())->findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: __('Produit supprimé.'));
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'form_name', 'form_type', 'form_price', 'form_stock',
            'form_description', 'form_is_published', 'editingProductId',
        ]);
        $this->form_type = 'digital';
    }

    public function typeLabel(string $type): string
    {
        return match ($type) {
            'digital' => __('Numérique'),
            'physical' => __('Physique'),
            'merch' => __('Merchandising'),
            default => $type,
        };
    }

    public function typeIcon(string $type): string
    {
        return match ($type) {
            'digital' => 'cloud-arrow-down',
            'physical' => 'cube',
            'merch' => 'shirt',
            default => 'cube',
        };
    }

    public function formatStock(?int $stock): string
    {
        if ($stock === null) return '∞';
        return (string) $stock;
    }
}; ?>

<div class="space-y-8">
    {{-- En-tête --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Ma boutique') }}</flux:heading>
            <flux:subheading>{{ __('Vendez vos produits physiques et numériques') }}</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="createProduct">
            <flux:icon.plus class="size-4" />
            {{ __('Nouveau produit') }}
        </flux:button>
    </div>

    {{-- Filtres --}}
    <div class="flex flex-wrap items-center gap-4">
        <div class="flex-1 min-w-[200px]">
            <flux:input wire:model.live="search" placeholder="{{ __('Rechercher un produit...') }}" icon="magnifying-glass" />
        </div>
        <flux:select wire:model.live="filter_type" class="w-44">
            <option value="">{{ __('Tous les types') }}</option>
            <option value="digital">{{ __('Numérique') }}</option>
            <option value="physical">{{ __('Physique') }}</option>
            <option value="merch">{{ __('Merchandising') }}</option>
        </flux:select>
        <flux:select wire:model.live="filter_status" class="w-44">
            <option value="">{{ __('Tous les statuts') }}</option>
            <option value="published">{{ __('Publié') }}</option>
            <option value="draft">{{ __('Brouillon') }}</option>
        </flux:select>
    </div>

    {{-- Formulaire --}}
    @if ($showForm)
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-6 flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon.shopping-bag class="size-5 text-zinc-600 dark:text-zinc-400" />
                </div>
                <div>
                    <flux:heading>{{ $editingProductId ? __('Modifier le produit') : __('Nouveau produit') }}</flux:heading>
                    <flux:subheading>{{ $editingProductId ? __('Modifiez les informations de votre produit') : __('Créez un produit physique ou numérique à vendre') }}</flux:subheading>
                </div>
            </div>

            <form wire:submit="saveProduct" class="space-y-5 max-w-2xl">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <flux:input wire:model="form_name" :label="__('Nom du produit')" required placeholder="{{ __('ex: T-shirt FanPay, Beat exclusif, etc.') }}" />
                    </div>

                    <flux:select wire:model="form_type" :label="__('Type')">
                        <option value="digital">{{ __('Numérique (téléchargement)') }}</option>
                        <option value="physical">{{ __('Physique (livraison)') }}</option>
                        <option value="merch">{{ __('Merchandising') }}</option>
                    </flux:select>

                    <flux:input wire:model="form_price" :label="__('Prix (FCFA)')" type="number" min="0" step="100" required placeholder="5000" />

                    @if ($form_type !== 'digital')
                        <flux:input wire:model="form_stock" :label="__('Stock disponible')" type="number" min="0" placeholder="100" description="{{ __('Laisser vide = illimité') }}" />
                    @else
                        <div class="flex items-end pb-2">
                            <flux:text class="text-xs text-zinc-500">{{ __('Produit numérique : stock illimité') }}</flux:text>
                        </div>
                    @endif

                    <div class="flex items-end pb-2 sm:col-span-2">
                        <flux:switch wire:model="form_is_published" :label="__('Publier immédiatement')" />
                    </div>
                </div>

                <div>
                    <flux:textarea wire:model="form_description" :label="__('Description')" rows="3" placeholder="{{ __('Description du produit...') }}" />
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <flux:button variant="primary" type="submit">
                        {{ $editingProductId ? __('Enregistrer') : __('Créer le produit') }}
                    </flux:button>
                    <flux:button variant="ghost" wire:click="cancelForm">
                        {{ __('Annuler') }}
                    </flux:button>
                </div>
            </form>
        </div>
    @endif

    {{-- Grille des produits --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse ($this->products as $product)
            <div class="group relative rounded-2xl border border-zinc-200 bg-white transition-all hover:shadow-lg hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600">
                {{-- Header with icon --}}
                <div class="flex items-center justify-center rounded-t-2xl bg-gradient-to-br from-zinc-50 to-zinc-100 p-8 dark:from-zinc-800 dark:to-zinc-900">
                    <div class="flex size-20 items-center justify-center rounded-2xl bg-white shadow-sm dark:bg-zinc-800">
                        <flux:icon :name="$this->typeIcon($product->type)" class="size-10 text-zinc-400" />
                    </div>

                    {{-- Badges --}}
                    <div class="absolute left-3 top-3 flex flex-col gap-1.5">
                        <span class="inline-flex items-center rounded-full bg-white/90 px-2.5 py-0.5 text-xs font-medium text-zinc-700 shadow-sm backdrop-blur-sm dark:bg-zinc-900/90 dark:text-zinc-300">
                            {{ $this->typeLabel($product->type) }}
                        </span>
                    </div>
                    <div class="absolute right-3 top-3">
                        @if ($product->is_published)
                            <span class="inline-flex items-center rounded-full bg-emerald-500/90 px-2.5 py-0.5 text-xs font-medium text-white shadow-sm backdrop-blur-sm">
                                {{ __('En vente') }}
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-amber-500/90 px-2.5 py-0.5 text-xs font-medium text-white shadow-sm backdrop-blur-sm">
                                {{ __('Brouillon') }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Infos --}}
                <div class="p-4">
                    <h3 class="font-semibold text-zinc-900 truncate dark:text-white">{{ $product->name }}</h3>
                    <div class="mt-1 flex items-center gap-3 text-xs text-zinc-500">
                        @if ($product->type !== 'digital')
                            <span>{{ __('Stock') }} : {{ $this->formatStock($product->stock) }}</span>
                        @else
                            <span>{{ __('Téléchargement') }}</span>
                        @endif
                    </div>

                    <p class="mt-2 text-lg font-bold text-zinc-900 dark:text-white">{{ number_format($product->price) }} FCFA</p>

                    {{-- Snippet description --}}
                    @if ($product->description)
                        <p class="mt-1.5 text-xs text-zinc-500 line-clamp-2">{{ $product->description }}</p>
                    @endif

                    {{-- Actions --}}
                    <div class="mt-4 flex items-center gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                        <flux:button variant="ghost" size="sm" icon="pencil-square" wire:click="editProduct({{ $product->id }})" :title="__('Modifier')" />
                        <flux:button variant="ghost" size="sm" :icon="$product->is_published ? 'eye-slash' : 'eye'" wire:click="togglePublish({{ $product->id }})" :title="$product->is_published ? __('Masquer') : __('Publier')" />
                        <flux:button variant="ghost" size="sm" icon="trash" wire:click="deleteProduct({{ $product->id }})" wire:confirm="{{ __('Supprimer ce produit ?') }}" :title="__('Supprimer')" class="text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/50" />
                        <flux:spacer />
                        <flux:badge size="sm" color="{{ $product->is_published ? 'emerald' : 'amber' }}">
                            {{ $product->is_published ? __('Actif') : __('Inactif') }}
                        </flux:badge>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full p-16 text-center">
                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon.shopping-bag class="size-8 text-zinc-400" />
                </div>
                @if ($this->search || $this->filter_type || $this->filter_status !== '')
                    <p class="font-medium text-zinc-900 dark:text-white">{{ __('Aucun résultat') }}</p>
                    <flux:text class="mt-1">{{ __('Essayez de modifier vos filtres.') }}</flux:text>
                @else
                    <p class="font-medium text-zinc-900 dark:text-white">{{ __('Bienvenue dans votre boutique') }}</p>
                    <flux:text class="mt-1">{{ __('Créez votre premier produit à vendre à vos fans.') }}</flux:text>
                    <flux:button variant="primary" wire:click="createProduct" class="mt-4">
                        {{ __('Créer mon premier produit') }}
                    </flux:button>
                @endif
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($this->products->hasPages())
        <div class="border-t border-zinc-200 pt-6 dark:border-zinc-700">
            {{ $this->products->links(data: ['scrollTo' => false]) }}
        </div>
    @endif
</div>
