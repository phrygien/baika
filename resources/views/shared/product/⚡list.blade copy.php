<?php
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;

new class extends Component
{
    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $sortBy = 'created_at';

    #[Url(history: true)]
    public string $filterCategory = '';

    #[Url(history: true)]
    public string $filterBrand = '';

    #[Url(history: true)]
    public string $minPrice = '';

    #[Url(history: true)]
    public string $maxPrice = '';

    public int   $page     = 1;
    public int   $perPage  = 20;
    public bool  $hasMore  = true;
    public array $products = [];
    public int   $total    = 0;

    public function mount(): void
    {
        $this->loadProducts(reset: true);
    }

    public function updatedSearch(): void         { $this->loadProducts(reset: true); }
    public function updatedSortBy(): void         { $this->loadProducts(reset: true); }
    public function updatedFilterCategory(): void { $this->loadProducts(reset: true); }
    public function updatedFilterBrand(): void    { $this->loadProducts(reset: true); }
    public function updatedMinPrice(): void       { $this->loadProducts(reset: true); }
    public function updatedMaxPrice(): void       { $this->loadProducts(reset: true); }

    public function loadMore(): void
    {
        if (!$this->hasMore) return;
        $this->page++;
        $this->loadProducts(reset: false);
    }

    protected function loadProducts(bool $reset = false): void
    {
        if ($reset) {
            $this->page     = 1;
            $this->products = [];
            $this->hasMore  = true;
        }

        $eagerLoad = [
            'category:id,name',
            'brand:id,name',
            'primaryImage:id,product_id,image_path,alt_text,is_primary',
        ];

        if (trim($this->search) !== '') {
            try {
                $filterBy = ['status:=approved', 'is_active:=true'];
                if ($this->filterCategory)  $filterBy[] = "category_id:={$this->filterCategory}";
                if ($this->filterBrand)     $filterBy[] = "brand_id:={$this->filterBrand}";
                if ($this->minPrice !== '') $filterBy[] = "base_price:>={$this->minPrice}";
                if ($this->maxPrice !== '') $filterBy[] = "base_price:<={$this->maxPrice}";

                $sortMap = [
                    'created_at'   => 'created_at:desc',
                    'price_asc'    => 'base_price:asc',
                    'price_desc'   => 'base_price:desc',
                    'best_sellers' => 'total_sold:desc',
                    'top_rated'    => 'average_rating:desc',
                ];

                $results = Product::search($this->search)
                    ->options([
                        'query_by'         => 'name,sku,short_description,brand_name,category_name',
                        'query_by_weights' => '10,8,3,4,3',
                        'filter_by'        => implode(' && ', $filterBy),
                        'sort_by'          => $sortMap[$this->sortBy] ?? 'created_at:desc',
                        'per_page'         => $this->perPage,
                        'page'             => $this->page,
                        'highlight_fields' => 'none',
                    ])
                    ->paginate($this->perPage, 'page', $this->page);

                $results->load($eagerLoad);
                $this->total   = $results->total();
                $this->hasMore = $results->hasMorePages();
                $newItems      = $this->mapProducts($results->items());

            } catch (\Throwable $e) {
                $newItems = $this->eloquentQuery($eagerLoad);
            }
        } else {
            $newItems = $this->eloquentQuery($eagerLoad);
        }

        $this->products = $reset
            ? $newItems
            : array_merge($this->products, $newItems);
    }

    protected function eloquentQuery(array $eagerLoad): array
    {
        $sortMap = [
            'created_at'   => ['created_at', 'desc'],
            'price_asc'    => ['base_price', 'asc'],
            'price_desc'   => ['base_price', 'desc'],
            'best_sellers' => ['total_sold', 'desc'],
            'top_rated'    => ['average_rating', 'desc'],
        ];
        [$col, $dir] = $sortMap[$this->sortBy] ?? ['created_at', 'desc'];

        $query = Product::query()
            ->select(['id','name','slug','sku','short_description','base_price','compare_at_price','currency','status','is_featured','average_rating','total_reviews','total_sold','category_id','brand_id','created_at'])
            ->with($eagerLoad)
            ->where('status', 'approved')
            ->where('is_active', true)
            ->when($this->filterCategory, fn($q) => $q->where('category_id', $this->filterCategory))
            ->when($this->filterBrand,    fn($q) => $q->where('brand_id', $this->filterBrand))
            ->when($this->minPrice !== '', fn($q) => $q->where('base_price', '>=', $this->minPrice))
            ->when($this->maxPrice !== '', fn($q) => $q->where('base_price', '<=', $this->maxPrice))
            ->orderBy($col, $dir)
            ->paginate($this->perPage, ['*'], 'page', $this->page);

        $this->total   = $query->total();
        $this->hasMore = $query->hasMorePages();

        return $this->mapProducts($query->items());
    }

    protected function mapProducts(array $items): array
    {
        return array_map(fn($p) => [
            'id'               => $p->id,
            'name'             => $p->name,
            'slug'             => $p->slug,
            'short_description'=> $p->short_description,
            'base_price'       => $p->base_price,
            'compare_at_price' => $p->compare_at_price,
            'currency'         => $p->currency ?? 'USD',
            'is_featured'      => $p->is_featured,
            'average_rating'   => $p->average_rating,
            'total_reviews'    => $p->total_reviews,
            'total_sold'       => $p->total_sold ?? 0,
            'image'            => $p->primaryImage?->image_path,
            'image_alt'        => $p->primaryImage?->alt_text ?? $p->name,
            'category'         => $p->category?->name,
            'brand'            => $p->brand?->name,
            'discount'         => $p->compare_at_price && $p->compare_at_price > $p->base_price
                                    ? (int) round((1 - $p->base_price / $p->compare_at_price) * 100)
                                    : 0,
        ], $items);
    }

    #[Computed]
    public function allCategories()
    {
        return Category::where('is_active', true)->roots()->orderBy('name')->get(['id', 'name', 'icon']);
    }

    #[Computed]
    public function allBrands()
    {
        return Brand::where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }

    public function clearFilters(): void
    {
        $this->search         = '';
        $this->sortBy         = 'created_at';
        $this->filterCategory = '';
        $this->filterBrand    = '';
        $this->minPrice       = '';
        $this->maxPrice       = '';
        $this->loadProducts(reset: true);
    }
};
?>

<div>

    {{-- ══════════════════════════════════════════════
         Barre filtre STICKY — se fige sous le navbar
         top-[57px] = hauteur du flux:header (56px + 1px border)
    ═══════════════════════════════════════════════════ --}}
    <div
        class="sticky top-[57px] z-20 border-b border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
        x-data
        x-init="
            // Détecte dynamiquement la hauteur du header au cas où elle varie
            const header = document.querySelector('[data-flux-header], flux-header, header');
            if (header) {
                const h = Math.round(header.getBoundingClientRect().height);
                $el.style.top = h + 'px';
            }
        "
    >
        <div class="overflow-x-auto">
            <div class="mx-auto flex max-w-7xl items-center gap-2 px-4 py-2">

                {{-- 🔍 Search --}}
                <div class="w-52 shrink-0">
                    <flux:input
                        wire:model.live.debounce.400ms="search"
                        icon="magnifying-glass"
                        placeholder="{{ __('Search...') }}"
                        clearable
                    />
                </div>

                <div class="h-4 w-px shrink-0 bg-zinc-200 dark:bg-zinc-700"></div>

                {{-- Sort pills --}}
                @foreach ([
                    'created_at'   => __('New'),
                    'best_sellers' => __('Best Sell'),
                    'price_asc'    => __('↑ Price'),
                    'price_desc'   => __('↓ Price'),
                    'top_rated'    => __('Top Rated'),
                ] as $val => $label)
                    <button
                        type="button"
                        wire:click="$set('sortBy', '{{ $val }}')"
                        class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold transition
                            {{ $sortBy === $val
                                ? 'bg-indigo-600 text-white'
                                : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach

                <div class="h-4 w-px shrink-0 bg-zinc-200 dark:bg-zinc-700"></div>

                {{-- Category --}}
                <div class="w-40 shrink-0">
                    <flux:select wire:model.live="filterCategory">
                        <flux:select.option value="">{{ __('All Categories') }}</flux:select.option>
                        @foreach ($this->allCategories as $cat)
                            <flux:select.option value="{{ $cat->id }}">
                                {{ $cat->icon ? $cat->icon . ' ' : '' }}{{ $cat->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                {{-- Brand --}}
                <div class="w-36 shrink-0">
                    <flux:select wire:model.live="filterBrand">
                        <flux:select.option value="">{{ __('All Brands') }}</flux:select.option>
                        @foreach ($this->allBrands as $brand)
                            <flux:select.option value="{{ $brand->id }}">{{ $brand->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                {{-- Price range --}}
                <div class="flex shrink-0 items-center gap-1.5">
                    <div class="w-20">
                        <flux:input
                            wire:model.live.debounce.600ms="minPrice"
                            type="number"
                            placeholder="Min"
                        />
                    </div>
                    <span class="text-xs text-zinc-400">–</span>
                    <div class="w-20">
                        <flux:input
                            wire:model.live.debounce.600ms="maxPrice"
                            type="number"
                            placeholder="Max"
                        />
                    </div>
                </div>

                {{-- Clear --}}
                @if ($search || $filterCategory || $filterBrand || $minPrice !== '' || $maxPrice !== '')
                    <flux:button
                        wire:click="clearFilters"
                        variant="ghost"
                        size="sm"
                        icon="x-mark"
                    >
                        {{ __('Clear') }}
                    </flux:button>
                @endif

                {{-- Total + engine --}}
                <div class="ml-auto flex shrink-0 items-center gap-2">
                    <flux:text size="sm" class="!text-zinc-400 whitespace-nowrap">
                        {{ number_format($total) }} {{ __('products') }}
                    </flux:text>
                    @if (trim($search) !== '')
                        <flux:badge size="sm" color="yellow" inset="top bottom">⚡ Typesense</flux:badge>
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- ── Grille produits ── --}}
    <div class="mx-auto max-w-7xl px-3 py-4 sm:px-6 lg:px-8">

        @if (!empty($products))

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
                @foreach ($products as $product)
                    <div wire:key="p-{{ $product['id'] }}">
                        <flux:card class="group !p-0 overflow-hidden transition duration-200 hover:shadow-md hover:-translate-y-0.5 cursor-pointer">
                            <a href="#" class="flex flex-col">

                                {{-- Image --}}
                                <div class="relative overflow-hidden bg-zinc-100 dark:bg-zinc-800">
                                    @if ($product['image'])
                                        <img
                                            src="{{ asset($product['image']) }}"
                                            alt="{{ $product['image_alt'] }}"
                                            loading="lazy"
                                            x-data="{ loaded: false }"
                                            x-init="const img = $el; if (img.complete && img.naturalWidth > 0) loaded = true;"
                                            x-bind:class="loaded ? 'opacity-100' : 'opacity-0'"
                                            x-on:load="loaded = true"
                                            x-on:error="loaded = true"
                                            class="aspect-square w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                            style="transition: opacity 0.3s ease, transform 0.3s ease"
                                        />
                                    @else
                                        <div class="flex aspect-square w-full items-center justify-center">
                                            <flux:icon name="photo" class="size-10 text-zinc-300" />
                                        </div>
                                    @endif

                                    @if ($product['discount'] > 0)
                                        <div class="absolute left-0 top-2 rounded-r-full bg-red-500 px-2 py-0.5 shadow-sm">
                                            <p class="text-xs font-bold text-white">-{{ $product['discount'] }}%</p>
                                        </div>
                                    @endif

                                    @if ($product['is_featured'])
                                        <div class="absolute right-1.5 top-1.5">
                                            <flux:badge size="sm" color="yellow" inset="top bottom">★</flux:badge>
                                        </div>
                                    @endif
                                </div>

                                {{-- Infos --}}
                                <div class="flex flex-1 flex-col gap-1 p-2">

                                    <div class="flex items-baseline gap-1.5">
                                        <flux:heading size="sm" class="!font-bold !text-indigo-600">
                                            {{ number_format($product['base_price'], 2) }}
                                            <span class="text-xs font-normal text-zinc-400">{{ $product['currency'] }}</span>
                                        </flux:heading>
                                        @if ($product['compare_at_price'])
                                            <flux:text size="sm" class="line-through !text-zinc-400">
                                                {{ number_format($product['compare_at_price'], 2) }}
                                            </flux:text>
                                        @endif
                                    </div>

                                    <flux:text size="sm" class="line-clamp-2 leading-tight">
                                        {{ $product['name'] }}
                                    </flux:text>

                                    <div class="mt-auto space-y-0.5 pt-1">
                                        @if ($product['average_rating'])
                                            <div class="flex items-center gap-1">
                                                @php $rating = round($product['average_rating']); @endphp
                                                <div class="flex items-center">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        <svg class="size-2.5 {{ $i <= $rating ? 'text-yellow-400' : 'text-zinc-200 dark:text-zinc-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                        </svg>
                                                    @endfor
                                                </div>
                                                <flux:text size="xs" class="!text-zinc-400">{{ number_format($product['average_rating'], 1) }}</flux:text>
                                            </div>
                                        @endif

                                        @if ($product['total_sold'] > 0)
                                            <flux:text size="xs" class="!text-zinc-400">
                                                @if ($product['total_sold'] >= 1000)
                                                    {{ number_format($product['total_sold'] / 1000, 1) }}k {{ __('sold') }}
                                                @else
                                                    {{ $product['total_sold'] }} {{ __('sold') }}
                                                @endif
                                            </flux:text>
                                        @endif
                                    </div>

                                </div>
                            </a>
                        </flux:card>
                    </div>
                @endforeach
            </div>

            {{-- Infinite scroll --}}
            @if ($hasMore)
                <div
                    x-data
                    x-intersect.threshold.10="$wire.loadMore()"
                    class="mt-8 flex items-center justify-center py-8"
                >
                    <div wire:loading.block wire:target="loadMore" class="flex items-center gap-2">
                        <flux:icon name="arrow-path" class="size-5 animate-spin text-indigo-500" />
                        <flux:text size="sm" class="!text-zinc-500">{{ __('Loading more...') }}</flux:text>
                    </div>
                    <div wire:loading.remove wire:target="loadMore" class="h-1 w-full"></div>
                </div>
            @else
                <div class="mt-10 flex flex-col items-center gap-1 py-6 text-center">
                    <flux:text size="sm" class="font-semibold !text-zinc-500">🎉 {{ __("You've seen it all!") }}</flux:text>
                    <flux:text size="sm" class="!text-zinc-400">{{ number_format($total) }} {{ __('products total') }}</flux:text>
                </div>
            @endif

        @else
            <div class="flex justify-center py-20">
                <flux:card class="w-full max-w-sm text-center">
                    <div class="mb-4 text-5xl">🔍</div>
                    <flux:heading size="lg">{{ __('No products found') }}</flux:heading>
                    <flux:text class="mt-2 mb-6">
                        {{ __('Try adjusting your search or filters to find what you\'re looking for.') }}
                    </flux:text>
                    <flux:button wire:click="clearFilters" variant="primary" icon="x-mark">
                        {{ __('Clear all filters') }}
                    </flux:button>
                </flux:card>
            </div>
        @endif

    </div>
</div>
