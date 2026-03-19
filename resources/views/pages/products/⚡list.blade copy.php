<?php
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Supplier;

new class extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $sortBy = 'created_at';

    #[Url(history: true)]
    public string $sortDirection = 'desc';

    #[Url(history: true)]
    public string $filterStatus = '';

    #[Url(history: true)]
    public string $filterCategory = '';

    #[Url(history: true)]
    public string $filterBrand = '';

    #[Url(history: true)]
    public string $filterSupplier = '';

    #[Url(history: true)]
    public string $filterFeatured = '';

    #[Url(history: true)]
    public bool $showTrashed = false;

    #[Url(history: true)]
    public int $perPage = 20;

    #[Url(history: true)]
    public string $viewMode = 'grid';

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }
    public function updatingFilterCategory(): void { $this->resetPage(); }
    public function updatingFilterBrand(): void { $this->resetPage(); }
    public function updatingFilterSupplier(): void { $this->resetPage(); }
    public function updatingFilterFeatured(): void { $this->resetPage(); }
    public function updatingPerPage(): void { $this->resetPage(); }

    public function toggleTrashed(): void
    {
        $this->showTrashed    = !$this->showTrashed;
        $this->filterStatus   = '';
        $this->filterCategory = '';
        $this->filterBrand    = '';
        $this->filterSupplier = '';
        $this->filterFeatured = '';
        $this->search         = '';
        $this->resetPage();
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
    }

    public function toggleActive(int $id): void
    {
        $product = Product::findOrFail($id);
        $product->update(['is_active' => !$product->is_active]);
        unset($this->products);

        $this->dispatch(
            'notify',
            variant: 'success',
            title: $product->is_active ? __('Product activated') : __('Product deactivated'),
            message: __(':name has been updated.', ['name' => $product->name]),
        );
    }

    public function restore(int $id): void
    {
        try {
            Product::withTrashed()->findOrFail($id)->restore();
            unset($this->products);
            $this->dispatch('notify', variant: 'success', title: __('Product restored'), message: __('The product has been restored.'));
        } catch (\Throwable $e) {
            $this->dispatch('notify', variant: 'warning', title: __('Restore failed'), message: __('An error occurred.'));
        }
    }

    public function forceDelete(int $id): void
    {
        try {
            Product::withTrashed()->findOrFail($id)->forceDelete();
            unset($this->products);
            $this->dispatch('notify', variant: 'success', title: __('Product permanently deleted'), message: __('The product has been permanently deleted.'));
        } catch (\Throwable $e) {
            $this->dispatch('notify', variant: 'warning', title: __('Delete failed'), message: __('An error occurred.'));
        }
    }

    #[On('product-created')]
    #[On('product-updated')]
    #[On('product-deleted')]
    public function refreshProducts(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->with(['supplier', 'category', 'brand', 'primaryImage'])
            ->when($this->showTrashed, fn($q) => $q->onlyTrashed())
            ->when($this->search, fn($q) =>
                $q->where(fn($q) =>
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('sku', 'like', "%{$this->search}%")
                      ->orWhere('slug', 'like', "%{$this->search}%")
                )
            )
            ->when(!$this->showTrashed && $this->filterStatus, fn($q) =>
                $q->where('status', $this->filterStatus)
            )
            ->when($this->filterCategory, fn($q) =>
                $q->where('category_id', $this->filterCategory)
            )
            ->when($this->filterBrand, fn($q) =>
                $q->where('brand_id', $this->filterBrand)
            )
            ->when($this->filterSupplier, fn($q) =>
                $q->where('supplier_id', $this->filterSupplier)
            )
            ->when(!$this->showTrashed && $this->filterFeatured !== '', fn($q) =>
                $q->where('is_featured', (bool) $this->filterFeatured)
            )
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->perPage);
    }

    #[Computed]
    public function categories()
    {
        return Category::roots()->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function brands()
    {
        return Brand::orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function suppliers()
    {
        return Supplier::approved()->orderBy('shop_name')->get(['id', 'shop_name']);
    }

    public function statusColor(string $status): string
    {
        return match($status) {
            'approved'  => 'green',
            'pending'   => 'yellow',
            'rejected'  => 'red',
            'draft'     => 'zinc',
            default     => 'zinc',
        };
    }
};
?>

<div>
    {{-- Skeleton pulse CSS --}}
    <style>
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 1000px 100%;
            animation: shimmer 1.5s infinite linear;
        }
        .dark .skeleton {
            background: linear-gradient(90deg, #2a2a2a 25%, #333333 50%, #2a2a2a 75%);
            background-size: 1000px 100%;
        }
        .img-lazy {
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        .img-lazy.loaded {
            opacity: 1;
        }
    </style>

    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Products') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage your product catalog') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    {{-- Toolbar --}}
    <div class="mb-4 space-y-3">

        {{-- Ligne 1 --}}
        <div class="flex flex-wrap items-center gap-3">
            <div class="w-72">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    icon="magnifying-glass"
                    placeholder="{{ __('Search by name, SKU, slug...') }}"
                />
            </div>

            <flux:spacer />

            {{-- View mode toggle --}}
            <div class="flex items-center gap-1 rounded-lg border border-zinc-200 p-1 dark:border-zinc-700">
                <button
                    type="button"
                    wire:click="setViewMode('grid')"
                    class="flex size-7 items-center justify-center rounded-md transition-colors
                        {{ $viewMode === 'grid' ? 'bg-zinc-800 text-white dark:bg-zinc-200 dark:text-zinc-800' : 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}"
                >
                    <svg class="size-4" fill="currentColor" viewBox="0 0 16 16">
                        <rect x="1" y="1" width="6" height="6" rx="1"/>
                        <rect x="9" y="1" width="6" height="6" rx="1"/>
                        <rect x="1" y="9" width="6" height="6" rx="1"/>
                        <rect x="9" y="9" width="6" height="6" rx="1"/>
                    </svg>
                </button>
                <button
                    type="button"
                    wire:click="setViewMode('list')"
                    class="flex size-7 items-center justify-center rounded-md transition-colors
                        {{ $viewMode === 'list' ? 'bg-zinc-800 text-white dark:bg-zinc-200 dark:text-zinc-800' : 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}"
                >
                    <svg class="size-4" fill="currentColor" viewBox="0 0 16 16">
                        <rect x="1" y="2" width="14" height="2.5" rx="1"/>
                        <rect x="1" y="6.75" width="14" height="2.5" rx="1"/>
                        <rect x="1" y="11.5" width="14" height="2.5" rx="1"/>
                    </svg>
                </button>
            </div>

            {{-- Per page --}}
            <div class="flex items-center gap-2">
                <span class="text-sm text-zinc-400">{{ __('Show') }}</span>
                <flux:select wire:model.live="perPage" class="w-20">
                    <flux:select.option value="12">12</flux:select.option>
                    <flux:select.option value="20">20</flux:select.option>
                    <flux:select.option value="40">40</flux:select.option>
                    <flux:select.option value="60">60</flux:select.option>
                    <flux:select.option value="100">100</flux:select.option>
                </flux:select>
            </div>

            <flux:button
                wire:click="toggleTrashed"
                :variant="$showTrashed ? 'danger' : 'ghost'"
                icon="trash"
                size="sm"
            >
                {{ $showTrashed ? __('View active') : __('Trash') }}
            </flux:button>

            @if (!$showTrashed)
                <flux:button variant="primary" wire:click="$dispatch('create-product')">
                    {{ __('Add Product') }}
                </flux:button>
            @endif
        </div>

        {{-- Ligne 2 : Filtres --}}
        @if (!$showTrashed)
            <div class="flex flex-wrap items-center gap-2">
                <flux:select wire:model.live="filterStatus" placeholder="{{ __('All statuses') }}" class="w-40">
                    <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                    <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
                    <flux:select.option value="approved">{{ __('Approved') }}</flux:select.option>
                    <flux:select.option value="rejected">{{ __('Rejected') }}</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="filterCategory" placeholder="{{ __('All categories') }}" class="w-44">
                    <flux:select.option value="">{{ __('All categories') }}</flux:select.option>
                    @foreach ($this->categories as $category)
                        <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="filterBrand" placeholder="{{ __('All brands') }}" class="w-40">
                    <flux:select.option value="">{{ __('All brands') }}</flux:select.option>
                    @foreach ($this->brands as $brand)
                        <flux:select.option value="{{ $brand->id }}">{{ $brand->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="filterSupplier" placeholder="{{ __('All suppliers') }}" class="w-44">
                    <flux:select.option value="">{{ __('All suppliers') }}</flux:select.option>
                    @foreach ($this->suppliers as $supplier)
                        <flux:select.option value="{{ $supplier->id }}">{{ $supplier->shop_name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="filterFeatured" placeholder="{{ __('All') }}" class="w-36">
                    <flux:select.option value="">{{ __('All') }}</flux:select.option>
                    <flux:select.option value="1">{{ __('Featured') }}</flux:select.option>
                    <flux:select.option value="0">{{ __('Not featured') }}</flux:select.option>
                </flux:select>

                <flux:select wire:model.live="sortBy" class="w-40">
                    <flux:select.option value="created_at">{{ __('Newest') }}</flux:select.option>
                    <flux:select.option value="name">{{ __('Name') }}</flux:select.option>
                    <flux:select.option value="base_price">{{ __('Price') }}</flux:select.option>
                    <flux:select.option value="total_sold">{{ __('Best sellers') }}</flux:select.option>
                    <flux:select.option value="average_rating">{{ __('Top rated') }}</flux:select.option>
                    <flux:select.option value="total_views">{{ __('Most viewed') }}</flux:select.option>
                </flux:select>
            </div>
        @endif
    </div>

    {{-- Banner trash --}}
    @if ($showTrashed)
        <div class="mb-4 flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 dark:border-red-800 dark:bg-red-900/20">
            <flux:icon name="trash" class="size-4 text-red-500" />
            <p class="text-sm text-red-700 dark:text-red-400">
                {{ __('Showing deleted products. You can restore or permanently delete them.') }}
            </p>
        </div>
    @endif

    {{-- Résultats --}}
    <div class="mb-3 flex items-center justify-between">
        <p class="text-sm text-zinc-400">
            {{ $this->products->total() }} {{ __('products found') }}
        </p>
        @if ($search || $filterStatus || $filterCategory || $filterBrand || $filterSupplier || $filterFeatured !== '')
            <button
                type="button"
                wire:click="$set('search', ''); $set('filterStatus', ''); $set('filterCategory', ''); $set('filterBrand', ''); $set('filterSupplier', ''); $set('filterFeatured', '')"
                class="flex items-center gap-1 text-xs text-zinc-400 hover:text-zinc-600"
            >
                <flux:icon name="x-mark" class="size-3" />
                {{ __('Clear filters') }}
            </button>
        @endif
    </div>

    {{-- Loading skeleton --}}
    <div wire:loading.block wire:target="search,sortBy,sortDirection,filterStatus,filterCategory,filterBrand,filterSupplier,filterFeatured,perPage,setViewMode,toggleTrashed,sort,gotoPage,previousPage,nextPage">
        @if ($viewMode === 'grid')
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                @for ($s = 0; $s < $perPage; $s++)
                    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <div class="skeleton aspect-square w-full"></div>
                        <div class="space-y-2 p-3">
                            <div class="skeleton h-4 w-3/4 rounded-md"></div>
                            <div class="skeleton h-3 w-1/2 rounded-md"></div>
                            <div class="flex items-center justify-between">
                                <div class="skeleton h-4 w-16 rounded-md"></div>
                                <div class="skeleton h-5 w-14 rounded-full"></div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        @else
            <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                @for ($s = 0; $s < min($perPage, 15); $s++)
                    <div class="flex items-center gap-4 border-b border-zinc-100 px-4 py-3 last:border-0 dark:border-zinc-800">
                        <div class="skeleton size-10 shrink-0 rounded-lg"></div>
                        <div class="flex-1 space-y-2">
                            <div class="skeleton h-4 w-48 rounded-md"></div>
                            <div class="skeleton h-3 w-24 rounded-md"></div>
                        </div>
                        <div class="skeleton h-4 w-20 rounded-md"></div>
                        <div class="skeleton h-5 w-16 rounded-full"></div>
                        <div class="skeleton h-4 w-12 rounded-md"></div>
                        <div class="skeleton size-7 rounded-md"></div>
                    </div>
                @endfor
            </div>
        @endif
    </div>

    {{-- Contenu réel --}}
    <div wire:loading.remove wire:target="search,sortBy,sortDirection,filterStatus,filterCategory,filterBrand,filterSupplier,filterFeatured,perPage,setViewMode,toggleTrashed,sort,gotoPage,previousPage,nextPage">

        {{-- ── GRID VIEW ── --}}
        @if ($viewMode === 'grid')
            @if ($this->products->isNotEmpty())
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                    @foreach ($this->products as $product)
                        <div
                            wire:key="grid-{{ $product->id }}"
                            class="group relative overflow-hidden rounded-xl border border-zinc-200 bg-white transition-all hover:border-zinc-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900 {{ $showTrashed ? 'opacity-60' : '' }}"
                        >
                            {{-- Image avec lazy load --}}
                            <div class="relative aspect-square overflow-hidden bg-zinc-100 dark:bg-zinc-800">
                                @if ($product->primaryImage?->image_path)
                                    <div class="skeleton absolute inset-0" x-data x-ref="skeleton"></div>
                                    <img
                                        src="{{ $product->primaryImage->image_path }}"
                                        alt="{{ $product->primaryImage->alt_text ?? $product->name }}"
                                        loading="lazy"
                                        class="img-lazy size-full object-cover transition-transform duration-300 group-hover:scale-105"
                                        x-data
                                        x-on:load="$el.classList.add('loaded'); $el.previousElementSibling?.remove()"
                                        x-on:error="$el.classList.add('loaded'); $el.previousElementSibling?.remove()"
                                    />
                                @else
                                    <div class="flex size-full items-center justify-center">
                                        <flux:icon name="photo" class="size-10 text-zinc-300" />
                                    </div>
                                @endif

                                {{-- Badges overlay --}}
                                <div class="absolute left-2 top-2 flex flex-col gap-1">
                                    @if ($product->is_featured)
                                        <flux:badge size="sm" color="yellow">★</flux:badge>
                                    @endif
                                    @if ($product->compare_at_price && $product->compare_at_price > $product->base_price)
                                        <flux:badge size="sm" color="red">
                                            -{{ (int) round((1 - $product->base_price / $product->compare_at_price) * 100) }}%
                                        </flux:badge>
                                    @endif
                                </div>

                                {{-- Actions overlay --}}
                                <div class="absolute right-2 top-2 opacity-0 transition-opacity group-hover:opacity-100">
                                    <flux:dropdown>
                                        <flux:button variant="filled" size="sm" icon="ellipsis-vertical" class="!size-7 !p-0 shadow-sm" />
                                        <flux:menu>
                                            <flux:menu.item icon="eye" wire:click="$dispatch('view-product', { id: {{ $product->id }} })">{{ __('View') }}</flux:menu.item>
                                            @if (!$showTrashed)
                                                <flux:menu.item icon="pencil-square" wire:click="$dispatch('edit-product', { id: {{ $product->id }} })">{{ __('Edit') }}</flux:menu.item>
                                                <flux:menu.separator />
                                                <flux:menu.item icon="trash" variant="danger" wire:click="$dispatch('delete-product', { id: {{ $product->id }} })">{{ __('Delete') }}</flux:menu.item>
                                            @else
                                                <flux:menu.item icon="arrow-path" wire:click="restore({{ $product->id }})">{{ __('Restore') }}</flux:menu.item>
                                                <flux:menu.separator />
                                                <flux:menu.item icon="trash" variant="danger" wire:click="forceDelete({{ $product->id }})" wire:confirm="{{ __('Permanently delete?') }}">{{ __('Delete permanently') }}</flux:menu.item>
                                            @endif
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </div>

                            {{-- Infos --}}
                            <div class="p-3">
                                <p class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                    {{ $product->name }}
                                </p>
                                <p class="mt-0.5 truncate text-xs text-zinc-400">
                                    {{ $product->category?->name ?? '—' }}
                                    @if ($product->brand)
                                        · {{ $product->brand->name }}
                                    @endif
                                </p>
                                <div class="mt-2 flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                                            {{ number_format($product->base_price, 2) }}
                                            <span class="text-xs font-normal text-zinc-400">{{ $product->currency }}</span>
                                        </p>
                                        @if ($product->compare_at_price)
                                            <p class="text-xs text-zinc-400 line-through">{{ number_format($product->compare_at_price, 2) }}</p>
                                        @endif
                                    </div>
                                    <flux:badge size="sm" :color="$this->statusColor($product->status ?? 'pending')" inset="top bottom">
                                        {{ ucfirst($product->status ?? 'pending') }}
                                    </flux:badge>
                                </div>
                                @if ($product->sku)
                                    <p class="mt-1 font-mono text-xs text-zinc-400">{{ $product->sku }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">{{ $this->products->links() }}</div>

            @else
                {{-- Empty grid --}}
                <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-200 py-20 text-center dark:border-zinc-700">
                    <flux:icon name="{{ $showTrashed ? 'trash' : 'cube' }}" class="mb-3 size-12 text-zinc-300 dark:text-zinc-600" />
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('No products found.') }}</p>
                </div>
            @endif

        {{-- ── LIST VIEW ── --}}
        @else
            <flux:table :paginate="$this->products">
                <flux:table.columns>
                    <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">{{ __('Product') }}</flux:table.column>
                    <flux:table.column>{{ __('Category / Brand') }}</flux:table.column>
                    <flux:table.column>{{ __('Supplier') }}</flux:table.column>
                    <flux:table.column sortable :sorted="$sortBy === 'base_price'" :direction="$sortDirection" wire:click="sort('base_price')">{{ __('Price') }}</flux:table.column>
                    @if (!$showTrashed)
                        <flux:table.column sortable :sorted="$sortBy === 'status'" :direction="$sortDirection" wire:click="sort('status')">{{ __('Status') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'total_sold'" :direction="$sortDirection" wire:click="sort('total_sold')">{{ __('Sales') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'average_rating'" :direction="$sortDirection" wire:click="sort('average_rating')">{{ __('Rating') }}</flux:table.column>
                    @else
                        <flux:table.column>{{ __('Deleted') }}</flux:table.column>
                    @endif
                    <flux:table.column sortable :sorted="$sortBy === 'created_at'" :direction="$sortDirection" wire:click="sort('created_at')">{{ __('Created') }}</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->products as $product)
                        <flux:table.row :key="$product->id" class="{{ $showTrashed ? 'opacity-60' : '' }}">

                            {{-- Image + Nom avec lazy load --}}
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <div
                                        class="relative size-10 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800"
                                        x-data="{ loaded: false }"
                                    >
                                        @if ($product->primaryImage?->image_path)
                                            {{-- Skeleton placeholder --}}
                                            <div
                                                class="skeleton absolute inset-0 rounded-lg"
                                                x-show="!loaded"
                                            ></div>
                                            <img
                                                src="{{ $product->primaryImage->image_path }}"
                                                alt="{{ $product->primaryImage->alt_text ?? $product->name }}"
                                                loading="lazy"
                                                class="size-full object-cover"
                                                x-on:load="loaded = true"
                                                x-on:error="loaded = true"
                                                x-bind:class="loaded ? 'opacity-100' : 'opacity-0'"
                                                style="transition: opacity 0.3s ease"
                                            />
                                        @else
                                            <div class="flex size-full items-center justify-center">
                                                <flux:icon name="photo" class="size-4 text-zinc-300" />
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                            {{ $product->name }}
                                            @if ($product->is_featured)
                                                <span class="text-yellow-400">★</span>
                                            @endif
                                        </p>
                                        @if ($product->sku)
                                            <p class="font-mono text-xs text-zinc-400">{{ $product->sku }}</p>
                                        @endif
                                    </div>
                                </div>
                            </flux:table.cell>

                            {{-- Category / Brand --}}
                            <flux:table.cell>
                                <div class="space-y-1">
                                    @if ($product->category)
                                        <flux:badge size="sm" color="zinc" inset="top bottom">{{ $product->category->name }}</flux:badge>
                                    @endif
                                    @if ($product->brand)
                                        <p class="text-xs text-zinc-400">{{ $product->brand->name }}</p>
                                    @endif
                                    @if (!$product->category && !$product->brand)
                                        <span class="text-sm text-zinc-400">—</span>
                                    @endif
                                </div>
                            </flux:table.cell>

                            {{-- Supplier --}}
                            <flux:table.cell>
                                @if ($product->supplier)
                                    <div class="flex items-center gap-2">
                                        <flux:avatar size="xs" src="{{ $product->supplier->logo }}" name="{{ $product->supplier->shop_name }}" />
                                        <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $product->supplier->shop_name }}</span>
                                    </div>
                                @else
                                    <span class="text-sm text-zinc-400">—</span>
                                @endif
                            </flux:table.cell>

                            {{-- Price --}}
                            <flux:table.cell>
                                <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                                    {{ number_format($product->base_price, 2) }}
                                    <span class="text-xs font-normal text-zinc-400">{{ $product->currency }}</span>
                                </p>
                                @if ($product->compare_at_price)
                                    <p class="text-xs text-zinc-400 line-through">{{ number_format($product->compare_at_price, 2) }}</p>
                                @endif
                            </flux:table.cell>

                            @if (!$showTrashed)
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$this->statusColor($product->status ?? 'pending')" inset="top bottom">
                                        {{ ucfirst($product->status ?? 'pending') }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ number_format($product->total_sold ?? 0) }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($product->average_rating)
                                        <div class="flex items-center gap-1">
                                            <flux:icon name="star" variant="solid" class="size-3.5 text-yellow-400" />
                                            <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ number_format($product->average_rating, 1) }}</span>
                                            <span class="text-xs text-zinc-400">({{ $product->total_reviews ?? 0 }})</span>
                                        </div>
                                    @else
                                        <span class="text-sm text-zinc-400">—</span>
                                    @endif
                                </flux:table.cell>
                            @else
                                <flux:table.cell class="whitespace-nowrap text-sm text-red-500">
                                    {{ $product->deleted_at?->diffForHumans() ?? '—' }}
                                </flux:table.cell>
                            @endif

                            <flux:table.cell class="whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $product->created_at->format('d M Y') }}
                            </flux:table.cell>

                            <flux:table.cell>
                                @if ($showTrashed)
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                        <flux:menu>
                                            <flux:menu.item icon="arrow-path" wire:click="restore({{ $product->id }})">{{ __('Restore') }}</flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item icon="trash" variant="danger" wire:click="forceDelete({{ $product->id }})" wire:confirm="{{ __('Permanently delete?') }}">{{ __('Delete permanently') }}</flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                @else
                                    <flux:dropdown>
                                        <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" inset="top bottom" />
                                        <flux:menu>
                                            <flux:menu.item icon="eye" wire:click="$dispatch('view-product', { id: {{ $product->id }} })">{{ __('View') }}</flux:menu.item>
                                            <flux:menu.item icon="pencil-square" wire:click="$dispatch('edit-product', { id: {{ $product->id }} })">{{ __('Edit') }}</flux:menu.item>
                                            <flux:menu.item icon="check-circle" wire:click="$dispatch('approve-product', { id: {{ $product->id }} })">{{ __('Approve / Reject') }}</flux:menu.item>
                                            <flux:menu.item :icon="$product->is_active ? 'eye-slash' : 'eye'" wire:click="toggleActive({{ $product->id }})">
                                                {{ $product->is_active ? __('Deactivate') : __('Activate') }}
                                            </flux:menu.item>
                                            <flux:menu.separator />
                                            <flux:menu.item icon="trash" variant="danger" wire:click="$dispatch('delete-product', { id: {{ $product->id }} })">{{ __('Delete') }}</flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                @endif
                            </flux:table.cell>

                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="9">
                                <div class="flex flex-col items-center justify-center py-12 text-center">
                                    <flux:icon name="{{ $showTrashed ? 'trash' : 'cube' }}" class="mb-3 size-10 text-zinc-300 dark:text-zinc-600" />
                                    @if ($showTrashed)
                                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('No deleted products.') }}</p>
                                    @elseif ($search || $filterStatus || $filterCategory || $filterBrand || $filterSupplier || $filterFeatured !== '')
                                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('No results for the applied filters.') }}</p>
                                        <p class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">{{ __('Try modifying your search or filters.') }}</p>
                                    @else
                                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('No products found.') }}</p>
                                        <p class="mt-1 text-sm text-zinc-400 dark:text-zinc-500">{{ __('Start by adding a product.') }}</p>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        @endif

    </div>

    {{-- Script lazy load natif --}}
    <script>
        document.addEventListener('livewire:navigated', () => {
            document.querySelectorAll('img[loading="lazy"]').forEach(img => {
                if (img.complete) {
                    img.classList.add('loaded');
                    img.previousElementSibling?.remove();
                }
            });
        });
    </script>

    <livewire:pages::products.create />
    <livewire:pages::products.edit />
    <livewire:pages::products.delete />
</div>
