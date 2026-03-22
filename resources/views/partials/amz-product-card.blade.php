{{--
    Amazon-style product card partial.
    Variables:
      $product  — array with product data
      $rank     — int, position in list (1-based)
      $compact  — bool, true = carousel mode (narrower), false = grid mode
--}}

@php
    $price   = number_format($product['base_price'], 2);
    $compare = $product['compare_at_price'] ? number_format($product['compare_at_price'], 2) : null;
    $disc    = $product['discount'];
    $rating  = $product['average_rating'] ? round($product['average_rating']) : null;
    $stars   = '';
    if ($rating) {
        for ($i = 1; $i <= 5; $i++) {
            $stars .= $i <= $rating ? '★' : '☆';
        }
    }
    $imgHeight = $compact ? 'aspect-[4/3]' : 'aspect-square';
@endphp

<div class="amz-card group">

    {{-- Image zone --}}
    <div class="amz-card-img {{ $imgHeight }}">

        {{-- Rank badge --}}
        @if ($rank <= 10)
            <span class="amz-rank {{ $rank <= 3 ? 'top3' : '' }}">#{{ $rank }}</span>
        @endif

        @if ($product['image'])
            <img
                src="{{ asset($product['image']) }}"
                alt="{{ $product['image_alt'] }}"
                loading="lazy"
                class="w-full h-full object-contain p-2 transition-transform duration-300 group-hover:scale-105"
                x-data="{ loaded: false }"
                x-init="if ($el.complete && $el.naturalWidth > 0) loaded = true"
                x-bind:style="loaded ? 'opacity:1' : 'opacity:0;transition:opacity 0.3s'"
                x-on:load="loaded = true"
                x-on:error="loaded = true"
            />
        @else
            <div class="w-full h-full flex items-center justify-center">
                <svg class="size-10 text-gray-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z"/>
                </svg>
            </div>
        @endif

        {{-- Discount badge --}}
        @if ($disc > 0)
            <span class="amz-badge-discount">-{{ $disc }}%</span>
        @endif

        {{-- Featured badge --}}
        @if ($product['is_featured'])
            <span class="amz-badge-featured">★ {{ __('Featured') }}</span>
        @endif
    </div>

    {{-- Body --}}
    <div class="amz-card-body">

        {{-- Product name --}}
        <a href="#" class="amz-card-title" title="{{ $product['name'] }}">
            {{ $product['name'] }}
        </a>

        {{-- Brand --}}
        @if ($product['brand'])
            <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ $product['brand'] }}</p>
        @endif

        {{-- Stars --}}
        @if ($rating)
            <div class="flex items-center gap-1 mt-0.5">
                <span class="amz-stars">{{ $stars }}</span>
                @if ($product['total_reviews'])
                    <span class="text-xs text-[#007185] dark:text-blue-400 hover:underline cursor-pointer">
                        {{ number_format($product['total_reviews']) }}
                    </span>
                @endif
            </div>
        @endif

        {{-- Price --}}
        <div class="mt-auto pt-1 flex flex-wrap items-baseline gap-0.5">
            <span class="amz-price">
                <sup style="font-size:0.65rem;vertical-align:super;font-weight:400">{{ $product['currency'] }}</sup>
                <span class="amz-price-whole">{{ $price }}</span>
            </span>
            @if ($compare)
                <span class="amz-price-compare">{{ $compare }}</span>
            @endif
            @if ($disc > 0)
                <span class="amz-price-discount">({{ $disc }}% off)</span>
            @endif
        </div>

        {{-- Sold count --}}
        @if ($product['total_sold'] > 0)
            <p class="amz-sold">
                @if ($product['total_sold'] >= 1000)
                    {{ number_format($product['total_sold'] / 1000, 1) }}k+ {{ __('bought') }}
                @else
                    {{ $product['total_sold'] }}+ {{ __('bought') }}
                @endif
            </p>
        @endif

        {{-- Add to cart button --}}
        @if (!$compact)
            <div class="pt-2">
                <button type="button" class="amz-btn-primary w-full">
                    {{ __('Add to Cart') }}
                </button>
            </div>
        @endif

    </div>
</div>
