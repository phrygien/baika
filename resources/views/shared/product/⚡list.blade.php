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

{{-- ══════════════════════════════════════════════════════════════════
     AMAZON-STYLE PRODUCT PAGE
     - Sticky sort/search bar at top (accounts for 98px Amazon navbar)
     - Left sidebar: categories + brands + price filter
     - Main: sort tabs + products grouped by category (horizontal scroll)
         OR flat grid when a category/search filter is active
══════════════════════════════════════════════════════════════════════ --}}

<div class="min-h-screen bg-[#f3f3f3] dark:bg-zinc-900" style="font-family: Arial, sans-serif;">

    {{-- ════════════════════════════════════════════
         STICKY TOP BAR — search + sort tabs
         top-[98px] = 60px topbar + 38px navbar
    ═════════════════════════════════════════════ --}}
    <div class="sticky top-[98px] z-20 bg-white dark:bg-zinc-800 border-b border-gray-200 dark:border-zinc-700 shadow-sm">
        <div class="mx-auto max-w-[1500px] px-4">

            {{-- Sort tabs (Amazon-style underline) --}}
            <div class="flex items-center gap-0 overflow-x-auto scrollbar-none">
                @foreach ([
                    'created_at'   => __('New Arrivals'),
                    'best_sellers' => __('Best Sellers'),
                    'top_rated'    => __('Top Rated'),
                    'price_asc'    => __('Price: Low to High'),
                    'price_desc'   => __('Price: High to Low'),
                ] as $val => $label)
                    <button
                        type="button"
                        wire:click="$set('sortBy', '{{ $val }}')"
                        class="relative whitespace-nowrap px-4 py-3 text-sm font-medium transition-colors
                            {{ $sortBy === $val
                                ? 'text-[#c7511f] border-b-2 border-[#c7511f]'
                                : 'text-gray-700 dark:text-gray-300 hover:text-[#c7511f] border-b-2 border-transparent' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach

                <div class="ml-auto flex items-center gap-3 py-2 pl-4 shrink-0">
                    {{-- Search input --}}
                    <div class="relative">
                        <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                        </svg>
                        <input
                            type="text"
                            wire:model.live.debounce.400ms="search"
                            placeholder="{{ __('Search products...') }}"
                            class="h-8 w-52 rounded border border-gray-300 bg-white pl-8 pr-3 text-sm focus:border-[#e77600] focus:outline-none focus:ring-1 focus:ring-[#e77600] dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                        />
                        @if ($search)
                            <button wire:click="$set('search','')" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        @endif
                    </div>

                    {{-- Result count --}}
                    <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        {{ number_format($total) }} {{ __('results') }}
                        @if (trim($search) !== '')
                            <span class="ml-1 inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-semibold text-yellow-800">⚡ Typesense</span>
                        @endif
                    </span>

                    {{-- Clear all --}}
                    @if ($search || $filterCategory || $filterBrand || $minPrice !== '' || $maxPrice !== '')
                        <button wire:click="clearFilters" class="flex items-center gap-1 text-xs text-[#007185] hover:text-[#c7511f] hover:underline whitespace-nowrap">
                            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            {{ __('Clear filters') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════
         MAIN LAYOUT: sidebar + content
    ═════════════════════════════════════════════ --}}
    <div class="mx-auto max-w-[1500px] px-4 py-4">
        <div class="flex gap-5 items-start">

            {{-- ════════════════════
                 LEFT SIDEBAR
            ══════════════════════ --}}
            <aside class="w-52 shrink-0 hidden md:block">

                {{-- Categories --}}
                <div class="bg-white dark:bg-zinc-800 rounded shadow-sm mb-3 overflow-hidden">
                    <div class="px-4 py-2.5 border-b border-gray-100 dark:border-zinc-700">
                        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100">{{ __('Department') }}</h3>
                    </div>
                    <ul class="py-1">
                        <li>
                            <button
                                wire:click="$set('filterCategory', '')"
                                class="w-full text-left px-4 py-1.5 text-sm transition-colors
                                    {{ $filterCategory === ''
                                        ? 'font-bold text-gray-900 dark:text-white'
                                        : 'text-[#007185] dark:text-blue-400 hover:text-[#c7511f] hover:underline' }}"
                            >
                                {{ __('Any Department') }}
                            </button>
                        </li>
                        @foreach ($this->allCategories as $cat)
                            <li>
                                <button
                                    wire:click="$set('filterCategory', '{{ $cat->id }}')"
                                    class="w-full text-left px-4 py-1.5 text-sm transition-colors
                                        {{ (string)$filterCategory === (string)$cat->id
                                            ? 'font-bold text-gray-900 dark:text-white border-l-2 border-[#c7511f] pl-3.5'
                                            : 'text-[#007185] dark:text-blue-400 hover:text-[#c7511f] hover:underline' }}"
                                >
                                    {{ $cat->icon ? $cat->icon . ' ' : '' }}{{ $cat->name }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Brands --}}
                @if (count($this->allBrands) > 0)
                <div class="bg-white dark:bg-zinc-800 rounded shadow-sm mb-3 overflow-hidden">
                    <div class="px-4 py-2.5 border-b border-gray-100 dark:border-zinc-700">
                        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100">{{ __('Brand') }}</h3>
                    </div>
                    <ul class="py-1 max-h-52 overflow-y-auto">
                        <li>
                            <button wire:click="$set('filterBrand', '')"
                                class="w-full text-left px-4 py-1 text-sm {{ $filterBrand === '' ? 'font-bold text-gray-900 dark:text-white' : 'text-[#007185] dark:text-blue-400 hover:underline' }}">
                                {{ __('All Brands') }}
                            </button>
                        </li>
                        @foreach ($this->allBrands as $brand)
                            <li>
                                <button wire:click="$set('filterBrand', '{{ $brand->id }}')"
                                    class="w-full text-left px-4 py-1 text-sm {{ (string)$filterBrand === (string)$brand->id ? 'font-bold text-gray-900 dark:text-white border-l-2 border-[#c7511f] pl-3.5' : 'text-[#007185] dark:text-blue-400 hover:underline' }}">
                                    {{ $brand->name }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- Price range --}}
                <div class="bg-white dark:bg-zinc-800 rounded shadow-sm mb-3 overflow-hidden">
                    <div class="px-4 py-2.5 border-b border-gray-100 dark:border-zinc-700">
                        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100">{{ __('Price') }}</h3>
                    </div>
                    <div class="px-4 py-3 flex items-center gap-2">
                        <input
                            type="number"
                            wire:model.live.debounce.600ms="minPrice"
                            placeholder="Min"
                            class="w-full rounded border border-gray-300 px-2 py-1 text-xs focus:border-[#e77600] focus:outline-none focus:ring-1 focus:ring-[#e77600] dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                        />
                        <span class="text-xs text-gray-400 shrink-0">–</span>
                        <input
                            type="number"
                            wire:model.live.debounce.600ms="maxPrice"
                            placeholder="Max"
                            class="w-full rounded border border-gray-300 px-2 py-1 text-xs focus:border-[#e77600] focus:outline-none focus:ring-1 focus:ring-[#e77600] dark:border-zinc-600 dark:bg-zinc-700 dark:text-white"
                        />
                    </div>
                    @if ($minPrice !== '' || $maxPrice !== '')
                        <div class="px-4 pb-3">
                            <button wire:click="$set('minPrice',''); $set('maxPrice','')" class="text-xs text-[#007185] hover:underline">
                                {{ __('Clear price filter') }}
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Featured toggle --}}
                <div class="bg-white dark:bg-zinc-800 rounded shadow-sm overflow-hidden">
                    <div class="px-4 py-2.5 border-b border-gray-100 dark:border-zinc-700">
                        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100">{{ __('Sort by') }}</h3>
                    </div>
                    <ul class="py-1">
                        @foreach ([
                            'created_at'   => __('New Arrivals'),
                            'best_sellers' => __('Best Sellers'),
                            'top_rated'    => __('Top Rated'),
                            'price_asc'    => __('Price: Low → High'),
                            'price_desc'   => __('Price: High → Low'),
                        ] as $val => $label)
                            <li>
                                <button wire:click="$set('sortBy', '{{ $val }}')"
                                    class="w-full text-left px-4 py-1.5 text-sm flex items-center gap-2 transition-colors
                                        {{ $sortBy === $val
                                            ? 'font-bold text-gray-900 dark:text-white'
                                            : 'text-[#007185] dark:text-blue-400 hover:underline' }}">
                                    @if ($sortBy === $val)
                                        <span class="size-1.5 rounded-full bg-[#c7511f] shrink-0"></span>
                                    @else
                                        <span class="size-1.5 rounded-full border border-gray-300 shrink-0"></span>
                                    @endif
                                    {{ $label }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>

            </aside>

            {{-- ════════════════════
                 MAIN CONTENT
            ══════════════════════ --}}
            <div class="flex-1 min-w-0">

                {{-- Page heading --}}
                <div class="mb-3">
                    <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">
                        @if ($filterCategory)
                            @php $catName = $this->allCategories->firstWhere('id', $filterCategory)?->name ?? __('Products'); @endphp
                            {{ __('Best in') }} {{ $catName }}
                        @elseif ($search)
                            {{ __('Results for') }} « {{ $search }} »
                        @else
                            @switch($sortBy)
                                @case('best_sellers') {{ __('Best Sellers') }} @break
                                @case('top_rated')    {{ __('Top Rated') }} @break
                                @case('price_asc')    {{ __('Products: Lowest Price') }} @break
                                @case('price_desc')   {{ __('Products: Highest Price') }} @break
                                @default              {{ __('New Arrivals') }}
                            @endswitch
                        @endif
                    </h1>
                    @if (!$filterCategory && !$search)
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Our most popular products by number of sales. Updated frequently.') }}
                        </p>
                    @endif
                </div>

                @php
                    // Group products by category for carousel display
                    // If a category filter is active or search is active → flat grid
                    $isFiltered = $filterCategory !== '' || trim($search) !== '' || $filterBrand !== '' || $minPrice !== '' || $maxPrice !== '';
                    $grouped = collect($products)->groupBy('category');
                @endphp

                @if (!empty($products))

                    @if ($isFiltered)
                        {{-- ═══════════════════════════════════════
                             FILTERED VIEW — flat product grid
                        ════════════════════════════════════════ --}}
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                            @foreach ($products as $index => $product)
                                <div wire:key="p-{{ $product['id'] }}">
                                    @include('partials.amz-product-card', [
                                        'product' => $product,
                                        'rank'    => $index + 1,
                                        'compact' => false,
                                    ])
                                </div>
                            @endforeach
                        </div>

                    @else
                        {{-- ═══════════════════════════════════════
                             DEFAULT VIEW — carousels by category
                        ════════════════════════════════════════ --}}
                        @foreach ($grouped as $categoryName => $categoryProducts)
                            @php $catProducts = $categoryProducts->values(); @endphp
                            <div class="mb-6 bg-white dark:bg-zinc-800 rounded shadow-sm overflow-hidden">

                                {{-- Section header --}}
                                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-zinc-700">
                                    <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">
                                        @switch($sortBy)
                                            @case('best_sellers') {{ __('Best Sellers in') }} @break
                                            @case('top_rated')    {{ __('Top Rated in') }} @break
                                            @case('price_asc')    {{ __('Lowest Price in') }} @break
                                            @case('price_desc')   {{ __('Highest Price in') }} @break
                                            @default              {{ __('New Arrivals in') }}
                                        @endswitch
                                        <span class="text-[#c7511f]">{{ $categoryName ?: __('Other') }}</span>
                                    </h2>
                                    <a href="#" class="text-sm text-[#007185] hover:text-[#c7511f] hover:underline shrink-0 ml-2">
                                        {{ __('See more') }} →
                                    </a>
                                </div>

                                {{-- Horizontal carousel --}}
                                <div
                                    x-data="{ scrollEl: null }"
                                    x-init="scrollEl = $refs.carousel"
                                    class="relative"
                                >
                                    {{-- Left arrow --}}
                                    <button
                                        @click="scrollEl.scrollBy({ left: -900, behavior: 'smooth' })"
                                        class="absolute left-0 top-0 bottom-0 z-10 flex items-center justify-center w-9 bg-white/90 dark:bg-zinc-800/90 hover:bg-gray-100 dark:hover:bg-zinc-700 shadow-md border-r border-gray-100 dark:border-zinc-700 transition"
                                        aria-label="Previous"
                                    >
                                        <svg class="size-5 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
                                        </svg>
                                    </button>

                                    {{-- Cards row --}}
                                    <div
                                        x-ref="carousel"
                                        class="flex overflow-x-auto scroll-smooth gap-0 pl-9 pr-9"
                                        style="scrollbar-width:none;-ms-overflow-style:none;"
                                    >
                                        @foreach ($catProducts as $index => $product)
                                            <div wire:key="cp-{{ $product['id'] }}" class="w-44 shrink-0 border-r border-gray-100 dark:border-zinc-700 last:border-r-0">
                                                @include('partials.amz-product-card', [
                                                    'product' => $product,
                                                    'rank'    => $index + 1,
                                                    'compact' => true,
                                                ])
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Right arrow --}}
                                    <button
                                        @click="scrollEl.scrollBy({ left: 900, behavior: 'smooth' })"
                                        class="absolute right-0 top-0 bottom-0 z-10 flex items-center justify-center w-9 bg-white/90 dark:bg-zinc-800/90 hover:bg-gray-100 dark:hover:bg-zinc-700 shadow-md border-l border-gray-100 dark:border-zinc-700 transition"
                                        aria-label="Next"
                                    >
                                        <svg class="size-5 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    @endif

                    {{-- Infinite scroll trigger --}}
                    @if ($hasMore)
                        <div
                            x-data
                            x-intersect.threshold.10="$wire.loadMore()"
                            class="flex items-center justify-center py-8"
                        >
                            <div wire:loading.block wire:target="loadMore" class="flex items-center gap-2 text-sm text-gray-500">
                                <svg class="size-4 animate-spin text-[#c7511f]" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                {{ __('Loading more products...') }}
                            </div>
                            <div wire:loading.remove wire:target="loadMore" class="h-1 w-full"></div>
                        </div>
                    @else
                        <div class="py-8 text-center border-t border-gray-200 dark:border-zinc-700">
                            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                                🎉 {{ __("You've seen all") }} {{ number_format($total) }} {{ __('products') }}
                            </p>
                        </div>
                    @endif

                @else
                    {{-- ═══ Empty state ═══ --}}
                    <div class="flex flex-col items-center gap-4 py-20 bg-white dark:bg-zinc-800 rounded shadow-sm">
                        <div class="text-5xl">🔍</div>
                        <div class="text-center">
                            <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ __('No results') }}</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 max-w-xs">
                                {{ __('Try adjusting your search or filters.') }}
                            </p>
                        </div>
                        <button wire:click="clearFilters"
                            class="mt-1 rounded border border-[#a88734] bg-gradient-to-b from-[#f7dfa5] to-[#f0c14b] px-4 py-1.5 text-sm font-medium text-[#111] shadow-sm hover:from-[#f0c14b] hover:to-[#e7a800] active:shadow-inner">
                            {{ __('Clear all filters') }}
                        </button>
                    </div>
                @endif

            </div>{{-- /main --}}
        </div>{{-- /flex --}}
    </div>{{-- /max-w --}}

</div>

{{-- ══════════════════════════════════════════════════════════════════
     PARTIAL: Amazon-style product card
     Usage: @include('livewire.partials.amz-product-card', ['product'=>$p, 'rank'=>$n, 'compact'=>bool])
══════════════════════════════════════════════════════════════════════ --}}
{{--
     NOTE: Extract the block below to resources/views/livewire/partials/amz-product-card.blade.php
     and remove it from here. It is inlined for convenience.
--}}
@php
if (!function_exists('amzProductCard')) {
    // Render inline via a Blade component or just use the @include below.
}
@endphp

{{-- ══════════════════════════════════════════════════════════════════
     INLINE STYLE BLOCK (Amazon card styles)
══════════════════════════════════════════════════════════════════════ --}}
@once
<style>
    /* Hide scrollbar on carousels */
    [x-ref="carousel"]::-webkit-scrollbar { display: none; }

    /* Amazon-style button */
    .amz-btn-primary {
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 20px;
        border: 1px solid #a88734;
        background: linear-gradient(to bottom, #f7dfa5, #f0c14b);
        color: #111; font-size: 0.75rem; font-weight: 500;
        padding: 0.3rem 0.75rem; cursor: pointer;
        box-shadow: 0 1px 0 rgba(255,255,255,0.4) inset;
        transition: background 0.1s;
        text-decoration: none;
    }
    .amz-btn-primary:hover {
        background: linear-gradient(to bottom, #f0c14b, #e7a800);
    }

    /* Star rating */
    .amz-stars { color: #c45500; font-size: 0.7rem; letter-spacing: -0.5px; }

    /* Rank badge */
    .amz-rank {
        position: absolute; top: 0; left: 0;
        background: #c7511f; color: #fff;
        font-size: 0.7rem; font-weight: 700;
        padding: 0.1rem 0.4rem 0.1rem 0.3rem;
        border-radius: 0 0 4px 0;
        z-index: 2;
        line-height: 1.5;
    }
    .amz-rank.top3 { background: #b12704; font-size: 0.75rem; }

    /* Product card */
    .amz-card {
        background: #fff;
        height: 100%;
        display: flex;
        flex-direction: column;
        transition: box-shadow 0.15s;
    }
    .dark .amz-card { background: #1f1f2e; }
    .amz-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.15); z-index: 1; }

    .amz-card-img {
        position: relative;
        background: #f7f7f7;
        overflow: hidden;
    }
    .dark .amz-card-img { background: #2a2a3a; }

    .amz-card-body { padding: 0.5rem 0.625rem 0.625rem; flex: 1; display: flex; flex-direction: column; gap: 0.2rem; }

    .amz-card-title {
        font-size: 0.8125rem;
        color: #007185;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.4;
        text-decoration: none;
    }
    .amz-card-title:hover { color: #c7511f; text-decoration: underline; }
    .dark .amz-card-title { color: #6bc2ce; }

    .amz-price { font-size: 0.9375rem; font-weight: 700; color: #b12704; }
    .dark .amz-price { color: #ff6161; }
    .amz-price-whole { font-size: 1.1em; }
    .amz-price-compare { font-size: 0.75rem; color: #565959; text-decoration: line-through; margin-left: 0.25rem; }
    .dark .amz-price-compare { color: #aaa; }
    .amz-price-discount { font-size: 0.72rem; color: #b12704; font-weight: 600; margin-left: 0.25rem; }

    .amz-sold { font-size: 0.68rem; color: #565959; }
    .dark .amz-sold { color: #aaa; }

    /* Discount badge on image */
    .amz-badge-discount {
        position: absolute; bottom: 4px; left: 4px;
        background: #c7511f; color: #fff;
        font-size: 0.65rem; font-weight: 700;
        padding: 0.1rem 0.35rem;
        border-radius: 3px;
        z-index: 2;
    }

    /* Featured star */
    .amz-badge-featured {
        position: absolute; top: 4px; right: 4px;
        background: #f0c14b; color: #111;
        font-size: 0.6rem; font-weight: 700;
        padding: 0.1rem 0.3rem;
        border-radius: 3px;
        z-index: 2;
    }
</style>
@endonce
