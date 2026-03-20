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
            $this->sortBy        = $column;
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
        unset($this->products);
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
    public function allCategories()
    {
        return Category::orderBy('depth')->orderBy('name')->get(['id', 'name', 'depth', 'icon']);
    }

    #[Computed]
    public function allBrands()
    {
        return Brand::orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function allSuppliers()
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

    public function paginationData(): array
    {
        $current = $this->products->currentPage();
        $last    = $this->products->lastPage();

        $pages = collect([1]);
        for ($i = max(2, $current - 2); $i <= min($last - 1, $current + 2); $i++) {
            $pages->push($i);
        }
        if ($last > 1) $pages->push($last);

        return [
            'current'   => $current,
            'last'      => $last,
            'pages'     => $pages->unique()->sort()->values()->toArray(),
            'hasMore'   => $this->products->hasMorePages(),
            'onFirst'   => $this->products->onFirstPage(),
            'firstItem' => $this->products->firstItem(),
            'lastItem'  => $this->products->lastItem(),
            'total'     => $this->products->total(),
        ];
    }
};
?>

<div>
    <style>
        @keyframes loading-bar {
            0%   { transform: translateX(-100%); }
            100% { transform: translateX(400%); }
        }
        .content-loading {
            opacity: 0.4;
            pointer-events: none;
            transition: opacity 0.15s ease;
        }
    </style>

    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Products') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage your product catalog') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    {{-- ── Toolbar ── --}}
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

            {{-- View mode --}}
            <div class="flex items-center gap-1 rounded-lg border border-zinc-200 p-1 dark:border-zinc-700">
                <button
                    type="button"
                    wire:click="setViewMode('grid')"
                    class="flex size-7 items-center justify-center rounded-md transition-colors
                        {{ $viewMode === 'grid'
                            ? 'bg-zinc-800 text-white dark:bg-zinc-200 dark:text-zinc-800'
                            : 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}"
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
                        {{ $viewMode === 'list'
                            ? 'bg-zinc-800 text-white dark:bg-zinc-200 dark:text-zinc-800'
                            : 'text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' }}"
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

                <flux:select wire:model.live="filterStatus" class="w-40">
                    <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                    <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
                    <flux:select.option value="approved">{{ __('Approved') }}</flux:select.option>
                    <flux:select.option value="rejected">{{ __('Rejected') }}</flux:select.option>
                </flux:select>

                {{-- Category --}}
                <div
                    x-data="{
                        open: false,
                        search: '',
                        get label() {
                            @if ($filterCategory)
                                return '{{ addslashes($this->allCategories->firstWhere('id', (int)$filterCategory)?->name ?? __('All categories')) }}';
                            @else
                                return '{{ __('All categories') }}';
                            @endif
                        }
                    }"
                    class="relative"
                    x-on:click.outside="open = false; search = ''"
                >
                    <button type="button" x-on:click="open = !open"
                        class="flex h-9 items-center gap-2 rounded-lg border px-3 text-sm shadow-sm transition
                            {{ $filterCategory ? 'border-blue-400 bg-blue-50 text-blue-600 dark:border-blue-700 dark:bg-blue-950/20 dark:text-blue-400' : 'border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800' }}"
                    >
                        <span x-text="label" class="max-w-[150px] truncate"></span>
                        <flux:icon name="chevron-down" class="size-3.5 shrink-0 text-zinc-400" />
                    </button>
                    <div x-show="open"
                        x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1"
                        class="absolute left-0 top-10 z-50 w-72 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
                        style="display:none"
                    >
                        <div class="border-b border-zinc-100 p-2 dark:border-zinc-800">
                            <input type="text" x-model="search" placeholder="{{ __('Search category...') }}"
                                class="w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-sm outline-none focus:border-blue-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:placeholder-zinc-500"
                                x-on:click.stop x-ref="catInput"
                                x-init="$watch('open', v => v && $nextTick(() => $refs.catInput?.focus()))"
                            />
                        </div>
                        <div class="max-h-56 overflow-y-auto py-1">
                            <button type="button" wire:click="$set('filterCategory', '')" x-on:click="open=false;search=''" class="w-full px-3 py-2 text-left text-sm text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-800">{{ __('All categories') }}</button>
                            @foreach ($this->allCategories as $category)
                                <button type="button" wire:click="$set('filterCategory', {{ $category->id }})"
                                    x-show="search==='' || '{{ strtolower(addslashes($category->name)) }}'.includes(search.toLowerCase())"
                                    x-on:click="open=false;search=''"
                                    class="flex w-full items-center gap-1 px-3 py-2 text-left text-sm transition {{ $filterCategory == $category->id ? 'bg-blue-50 font-medium text-blue-600 dark:bg-blue-950/20 dark:text-blue-400' : 'text-zinc-700 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800' }}"
                                >
                                    @if (($category->depth ?? 0) > 0)<span class="shrink-0 text-xs text-zinc-300 dark:text-zinc-600">{{ str_repeat('— ', $category->depth) }}</span>@endif
                                    @if ($category->icon)<span class="shrink-0">{{ $category->icon }}</span>@endif
                                    <span class="truncate">{{ $category->name }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Brand --}}
                <div
                    x-data="{
                        open: false,
                        search: '',
                        get label() {
                            @if ($filterBrand)
                                return '{{ addslashes($this->allBrands->firstWhere('id', (int)$filterBrand)?->name ?? __('All brands')) }}';
                            @else
                                return '{{ __('All brands') }}';
                            @endif
                        }
                    }"
                    class="relative"
                    x-on:click.outside="open = false; search = ''"
                >
                    <button type="button" x-on:click="open = !open"
                        class="flex h-9 items-center gap-2 rounded-lg border px-3 text-sm shadow-sm transition
                            {{ $filterBrand ? 'border-blue-400 bg-blue-50 text-blue-600 dark:border-blue-700 dark:bg-blue-950/20 dark:text-blue-400' : 'border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800' }}"
                    >
                        <span x-text="label" class="max-w-[130px] truncate"></span>
                        <flux:icon name="chevron-down" class="size-3.5 shrink-0 text-zinc-400" />
                    </button>
                    <div x-show="open"
                        x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1"
                        class="absolute left-0 top-10 z-50 w-56 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
                        style="display:none"
                    >
                        <div class="border-b border-zinc-100 p-2 dark:border-zinc-800">
                            <input type="text" x-model="search" placeholder="{{ __('Search brand...') }}"
                                class="w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-sm outline-none focus:border-blue-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:placeholder-zinc-500"
                                x-on:click.stop x-ref="brandInput"
                                x-init="$watch('open', v => v && $nextTick(() => $refs.brandInput?.focus()))"
                            />
                        </div>
                        <div class="max-h-56 overflow-y-auto py-1">
                            <button type="button" wire:click="$set('filterBrand', '')" x-on:click="open=false;search=''" class="w-full px-3 py-2 text-left text-sm text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-800">{{ __('All brands') }}</button>
                            @foreach ($this->allBrands as $brand)
                                <button type="button" wire:click="$set('filterBrand', {{ $brand->id }})"
                                    x-show="search==='' || '{{ strtolower(addslashes($brand->name)) }}'.includes(search.toLowerCase())"
                                    x-on:click="open=false;search=''"
                                    class="w-full truncate px-3 py-2 text-left text-sm transition {{ $filterBrand == $brand->id ? 'bg-blue-50 font-medium text-blue-600 dark:bg-blue-950/20 dark:text-blue-400' : 'text-zinc-700 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800' }}"
                                >{{ $brand->name }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Supplier --}}
                <div
                    x-data="{
                        open: false,
                        search: '',
                        get label() {
                            @if ($filterSupplier)
                                return '{{ addslashes($this->allSuppliers->firstWhere('id', (int)$filterSupplier)?->shop_name ?? __('All suppliers')) }}';
                            @else
                                return '{{ __('All suppliers') }}';
                            @endif
                        }
                    }"
                    class="relative"
                    x-on:click.outside="open = false; search = ''"
                >
                    <button type="button" x-on:click="open = !open"
                        class="flex h-9 items-center gap-2 rounded-lg border px-3 text-sm shadow-sm transition
                            {{ $filterSupplier ? 'border-blue-400 bg-blue-50 text-blue-600 dark:border-blue-700 dark:bg-blue-950/20 dark:text-blue-400' : 'border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800' }}"
                    >
                        <span x-text="label" class="max-w-[150px] truncate"></span>
                        <flux:icon name="chevron-down" class="size-3.5 shrink-0 text-zinc-400" />
                    </button>
                    <div x-show="open"
                        x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1"
                        class="absolute left-0 top-10 z-50 w-64 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
                        style="display:none"
                    >
                        <div class="border-b border-zinc-100 p-2 dark:border-zinc-800">
                            <input type="text" x-model="search" placeholder="{{ __('Search supplier...') }}"
                                class="w-full rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-1.5 text-sm outline-none focus:border-blue-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:placeholder-zinc-500"
                                x-on:click.stop x-ref="supplierInput"
                                x-init="$watch('open', v => v && $nextTick(() => $refs.supplierInput?.focus()))"
                            />
                        </div>
                        <div class="max-h-56 overflow-y-auto py-1">
                            <button type="button" wire:click="$set('filterSupplier', '')" x-on:click="open=false;search=''" class="w-full px-3 py-2 text-left text-sm text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-800">{{ __('All suppliers') }}</button>
                            @foreach ($this->allSuppliers as $supplier)
                                <button type="button" wire:click="$set('filterSupplier', {{ $supplier->id }})"
                                    x-show="search==='' || '{{ strtolower(addslashes($supplier->shop_name)) }}'.includes(search.toLowerCase())"
                                    x-on:click="open=false;search=''"
                                    class="w-full truncate px-3 py-2 text-left text-sm transition {{ $filterSupplier == $supplier->id ? 'bg-blue-50 font-medium text-blue-600 dark:bg-blue-950/20 dark:text-blue-400' : 'text-zinc-700 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800' }}"
                                >{{ $supplier->shop_name }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <flux:select wire:model.live="filterFeatured" class="w-36">
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
            <p class="text-sm text-red-700 dark:text-red-400">{{ __('Showing deleted products. You can restore or permanently delete them.') }}</p>
        </div>
    @endif

    {{-- Info --}}
    <div class="mb-2 flex items-center justify-between">
        <p class="text-sm text-zinc-400">{{ number_format($this->products->total()) }} {{ __('products found') }}</p>
        @if ($search || $filterStatus || $filterCategory || $filterBrand || $filterSupplier || $filterFeatured !== '')
            <button type="button"
                wire:click="$set('search','');$set('filterStatus','');$set('filterCategory','');$set('filterBrand','');$set('filterSupplier','');$set('filterFeatured','')"
                class="flex items-center gap-1 text-xs text-zinc-400 hover:text-zinc-600"
            >
                <flux:icon name="x-mark" class="size-3" />{{ __('Clear filters') }}
            </button>
        @endif
    </div>

    {{-- Loading bar --}}
    <div class="relative mb-3 h-0.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
        <div
            wire:loading
            wire:target="search,sortBy,sortDirection,filterStatus,filterCategory,filterBrand,filterSupplier,filterFeatured,perPage,setViewMode,toggleTrashed,sort,gotoPage,previousPage,nextPage"
            style="display:none; animation: loading-bar 0.9s ease-in-out infinite"
            class="absolute inset-y-0 w-1/3 rounded-full bg-blue-500"
        ></div>
        <style>@keyframes loading-bar { 0%{transform:translateX(-100%)} 100%{transform:translateX(400%)} }</style>
    </div>

    {{-- ══ GRID VIEW ══ --}}
    @if ($viewMode === 'grid')
        <div
            wire:loading.class="content-loading"
            wire:target="search,sortBy,sortDirection,filterStatus,filterCategory,filterBrand,filterSupplier,filterFeatured,perPage,setViewMode,toggleTrashed,sort,gotoPage,previousPage,nextPage"
        >
            @if ($this->products->isNotEmpty())
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                    @foreach ($this->products as $product)
                        <div
                            wire:key="grid-{{ $product->id }}"
                            class="group relative overflow-hidden rounded-xl border border-zinc-200 bg-white transition-all hover:border-zinc-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900 {{ $showTrashed ? 'opacity-60' : '' }}"
                        >
                            {{-- Image avec skeleton Alpine --}}
                            <div class="relative aspect-square overflow-hidden bg-zinc-100 dark:bg-zinc-800">
                                @if ($product->primaryImage?->image_path)
                                    <div x-data="{ loaded: false }" class="size-full">
                                        {{-- Skeleton pulsant — disparaît à la fin du chargement image --}}
                                        <div
                                            x-show="!loaded"
                                            class="absolute inset-0 animate-pulse bg-zinc-200 dark:bg-zinc-700"
                                        ></div>
                                        <img
                                            src="{{ $product->primaryImage->image_path }}"
                                            alt="{{ $product->primaryImage->alt_text ?? $product->name }}"
                                            loading="lazy"
                                            class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                                            x-bind:class="loaded ? 'opacity-100' : 'opacity-0'"
                                            x-on:load="loaded = true"
                                            x-on:error="loaded = true"
                                            style="transition: opacity 0.35s ease"
                                        />
                                    </div>
                                @else
                                    <div class="flex size-full items-center justify-center">
                                        <flux:icon name="photo" class="size-10 text-zinc-300" />
                                    </div>
                                @endif

                                {{-- Badges --}}
                                <div class="absolute left-2 top-2 flex flex-col gap-1">
                                    @if ($product->is_featured)
                                        <flux:badge size="sm" color="yellow">★</flux:badge>
                                    @endif
                                    @if ($product->compare_at_price && $product->compare_at_price > $product->base_price)
                                        <flux:badge size="sm" color="red">-{{ (int) round((1 - $product->base_price / $product->compare_at_price) * 100) }}%</flux:badge>
                                    @endif
                                </div>

                                {{-- Actions --}}
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

                            <div class="p-3">
                                <p class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $product->name }}</p>
                                <p class="mt-0.5 truncate text-xs text-zinc-400">
                                    {{ $product->category?->name ?? '—' }}
                                    @if ($product->brand) · {{ $product->brand->name }} @endif
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

                {{-- Pagination --}}
                @php $pg = $this->paginationData(); @endphp
                <div class="mt-6 flex items-center justify-between">
                    <p class="text-sm text-zinc-400">
                        {{ __('Showing') }} <span class="font-medium text-zinc-600 dark:text-zinc-300">{{ $pg['firstItem'] }}</span>
                        {{ __('to') }} <span class="font-medium text-zinc-600 dark:text-zinc-300">{{ $pg['lastItem'] }}</span>
                        {{ __('of') }} <span class="font-medium text-zinc-600 dark:text-zinc-300">{{ number_format($pg['total']) }}</span>
                        {{ __('results') }}
                    </p>
                    <div class="flex items-center gap-1">
                        @if ($pg['onFirst'])
                            <span class="flex size-8 items-center justify-center rounded-lg text-zinc-300 dark:text-zinc-600"><flux:icon name="chevron-left" class="size-4" /></span>
                        @else
                            <button wire:click="previousPage" class="flex size-8 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-zinc-100 dark:hover:bg-zinc-800"><flux:icon name="chevron-left" class="size-4" /></button>
                        @endif
                        @php $prev = null; @endphp
                        @foreach ($pg['pages'] as $page)
                            @if ($prev !== null && $page - $prev > 1)
                                <span class="flex size-8 items-center justify-center text-sm text-zinc-400">…</span>
                            @endif
                            @if ($page === $pg['current'])
                                <span class="flex size-8 items-center justify-center rounded-lg bg-zinc-800 text-sm font-semibold text-white dark:bg-zinc-200 dark:text-zinc-800">{{ $page }}</span>
                            @else
                                <button wire:click="gotoPage({{ $page }})" class="flex size-8 items-center justify-center rounded-lg text-sm text-zinc-600 transition hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800">{{ $page }}</button>
                            @endif
                            @php $prev = $page; @endphp
                        @endforeach
                        @if ($pg['hasMore'])
                            <button wire:click="nextPage" class="flex size-8 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-zinc-100 dark:hover:bg-zinc-800"><flux:icon name="chevron-right" class="size-4" /></button>
                        @else
                            <span class="flex size-8 items-center justify-center rounded-lg text-zinc-300 dark:text-zinc-600"><flux:icon name="chevron-right" class="size-4" /></span>
                        @endif
                    </div>
                </div>

            @else
                <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-200 py-20 text-center dark:border-zinc-700">
                    <flux:icon name="{{ $showTrashed ? 'trash' : 'cube' }}" class="mb-3 size-12 text-zinc-300 dark:text-zinc-600" />
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('No products found.') }}</p>
                    <p class="mt-1 text-sm text-zinc-400">{{ __('Try adjusting your filters or add a new product.') }}</p>
                </div>
            @endif
        </div>

    {{-- ══ LIST VIEW ══ --}}
    @else
        <div
            wire:loading.class="content-loading"
            wire:target="search,sortBy,sortDirection,filterStatus,filterCategory,filterBrand,filterSupplier,filterFeatured,perPage,setViewMode,toggleTrashed,sort,gotoPage,previousPage,nextPage"
        >
            <flux:table>
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

                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    {{-- Image avec skeleton Alpine --}}
                                    <div class="relative size-10 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                        @if ($product->primaryImage?->image_path)
                                            <div x-data="{ loaded: false }" class="size-full">
                                                <div
                                                    x-show="!loaded"
                                                    class="absolute inset-0 animate-pulse rounded-lg bg-zinc-200 dark:bg-zinc-700"
                                                ></div>
                                                <img
                                                    src="{{ $product->primaryImage->image_path }}"
                                                    alt="{{ $product->name }}"
                                                    loading="lazy"
                                                    class="size-full object-cover"
                                                    x-bind:class="loaded ? 'opacity-100' : 'opacity-0'"
                                                    x-on:load="loaded = true"
                                                    x-on:error="loaded = true"
                                                    style="transition: opacity 0.3s ease"
                                                />
                                            </div>
                                        @else
                                            <div class="flex size-full items-center justify-center">
                                                <flux:icon name="photo" class="size-4 text-zinc-300" />
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                            {{ $product->name }}
                                            @if ($product->is_featured) <span class="text-yellow-400">★</span> @endif
                                        </p>
                                        @if ($product->sku)
                                            <p class="font-mono text-xs text-zinc-400">{{ $product->sku }}</p>
                                        @endif
                                    </div>
                                </div>
                            </flux:table.cell>

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
                                <flux:table.cell class="text-sm text-zinc-500">{{ number_format($product->total_sold ?? 0) }}</flux:table.cell>
                                <flux:table.cell>
                                    @if ($product->average_rating)
                                        <div class="flex items-center gap-1">
                                            <flux:icon name="star" variant="solid" class="size-3.5 text-yellow-400" />
                                            <span class="text-sm">{{ number_format($product->average_rating, 1) }}</span>
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

                            <flux:table.cell class="whitespace-nowrap text-sm text-zinc-500">
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
                                        <p class="text-sm font-medium text-zinc-500">{{ __('No deleted products.') }}</p>
                                    @elseif ($search || $filterStatus || $filterCategory || $filterBrand || $filterSupplier || $filterFeatured !== '')
                                        <p class="text-sm font-medium text-zinc-500">{{ __('No results for the applied filters.') }}</p>
                                        <p class="mt-1 text-sm text-zinc-400">{{ __('Try modifying your search or filters.') }}</p>
                                    @else
                                        <p class="text-sm font-medium text-zinc-500">{{ __('No products found.') }}</p>
                                        <p class="mt-1 text-sm text-zinc-400">{{ __('Start by adding a product.') }}</p>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            {{-- Pagination list --}}
            @php $pg = $this->paginationData(); @endphp
            <div class="mt-5 flex items-center justify-between">
                <p class="text-sm text-zinc-400">
                    {{ __('Showing') }} <span class="font-medium text-zinc-600 dark:text-zinc-300">{{ $pg['firstItem'] }}</span>
                    {{ __('to') }} <span class="font-medium text-zinc-600 dark:text-zinc-300">{{ $pg['lastItem'] }}</span>
                    {{ __('of') }} <span class="font-medium text-zinc-600 dark:text-zinc-300">{{ number_format($pg['total']) }}</span>
                    {{ __('results') }}
                </p>
                <div class="flex items-center gap-1">
                    @if ($pg['onFirst'])
                        <span class="flex size-8 items-center justify-center rounded-lg text-zinc-300 dark:text-zinc-600"><flux:icon name="chevron-left" class="size-4" /></span>
                    @else
                        <button wire:click="previousPage" class="flex size-8 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-zinc-100 dark:hover:bg-zinc-800"><flux:icon name="chevron-left" class="size-4" /></button>
                    @endif
                    @php $prev = null; @endphp
                    @foreach ($pg['pages'] as $page)
                        @if ($prev !== null && $page - $prev > 1)
                            <span class="flex size-8 items-center justify-center text-sm text-zinc-400">…</span>
                        @endif
                        @if ($page === $pg['current'])
                            <span class="flex size-8 items-center justify-center rounded-lg bg-zinc-800 text-sm font-semibold text-white dark:bg-zinc-200 dark:text-zinc-800">{{ $page }}</span>
                        @else
                            <button wire:click="gotoPage({{ $page }})" class="flex size-8 items-center justify-center rounded-lg text-sm text-zinc-600 transition hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800">{{ $page }}</button>
                        @endif
                        @php $prev = $page; @endphp
                    @endforeach
                    @if ($pg['hasMore'])
                        <button wire:click="nextPage" class="flex size-8 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-zinc-100 dark:hover:bg-zinc-800"><flux:icon name="chevron-right" class="size-4" /></button>
                    @else
                        <span class="flex size-8 items-center justify-center rounded-lg text-zinc-300 dark:text-zinc-600"><flux:icon name="chevron-right" class="size-4" /></span>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <livewire:pages::products.create />
    <livewire:pages::products.edit />
    <livewire:pages::products.delete />
</div>
