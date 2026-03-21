<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <style>

            /* ============================================
               BOTTOM NAV — iOS 26 Liquid Glass
            ============================================ */
            .bottom-nav { display: none; }

            @media (max-width: 1023px) {
                body { padding-bottom: 6rem; }

                footer { display: none !important; }

                /* --- Liquid Glass Bottom Bar --- */
                .bottom-nav {
                    display: flex;
                    position: fixed;
                    bottom: 1rem;
                    left: 50%;
                    transform: translateX(-50%);
                    z-index: 50;
                    width: calc(100% - 2.5rem);
                    max-width: 420px;
                    height: 3.75rem;
                    align-items: center;
                    justify-content: space-around;
                    padding: 0 0.5rem;
                    padding-bottom: env(safe-area-inset-bottom);

                    /* Liquid glass core */
                    background: rgba(255, 255, 255, 0.18);
                    backdrop-filter: blur(28px) saturate(180%);
                    -webkit-backdrop-filter: blur(28px) saturate(180%);
                    border-radius: 2rem;

                    /* Glass border — top highlight + subtle outer ring */
                    border: 1px solid rgba(255, 255, 255, 0.45);
                    box-shadow:
                        /* top specular highlight */
                        inset 0 1px 0 rgba(255, 255, 255, 0.55),
                        /* bottom rim */
                        inset 0 -1px 0 rgba(0, 0, 0, 0.08),
                        /* outer glow */
                        0 8px 32px rgba(0, 0, 0, 0.18),
                        0 2px 8px rgba(0, 0, 0, 0.10);
                }

                /* Dark mode glass */
                @media (prefers-color-scheme: dark) {
                    .bottom-nav {
                        background: rgba(20, 20, 30, 0.55);
                        border-color: rgba(255, 255, 255, 0.14);
                        box-shadow:
                            inset 0 1px 0 rgba(255, 255, 255, 0.12),
                            inset 0 -1px 0 rgba(0, 0, 0, 0.3),
                            0 8px 32px rgba(0, 0, 0, 0.45),
                            0 2px 8px rgba(0, 0, 0, 0.3);
                    }
                }

                /* --- Nav items --- */
                .bottom-nav-item {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    gap: 0.2rem;
                    flex: 1;
                    height: 100%;
                    color: rgba(30, 30, 46, 0.65);
                    text-decoration: none;
                    background: none;
                    border: none;
                    cursor: pointer;
                    transition: color 0.2s ease, transform 0.15s ease;
                    position: relative;
                    -webkit-tap-highlight-color: transparent;
                    border-radius: 1.5rem;
                }

                @media (prefers-color-scheme: dark) {
                    .bottom-nav-item { color: rgba(255,255,255,0.5); }
                    .bottom-nav-item:hover, .bottom-nav-item.active { color: #fff; }
                }

                .bottom-nav-item:hover { color: rgba(0,0,0,0.9); transform: scale(1.08); }

                .bottom-nav-item.active {
                    color: #3730a3;
                }

                /* Liquid pill indicator for active item */
                .bottom-nav-item.active::before {
                    content: '';
                    position: absolute;
                    inset: 6px 4px;
                    border-radius: 1.25rem;
                    background: rgba(99, 102, 241, 0.13);
                    backdrop-filter: blur(8px);
                    border: 1px solid rgba(99, 102, 241, 0.2);
                    box-shadow: inset 0 1px 0 rgba(255,255,255,0.4);
                    z-index: -1;
                }

                .bottom-nav-item svg {
                    width: 1.4rem;
                    height: 1.4rem;
                    transition: transform 0.2s cubic-bezier(.34,1.56,.64,1);
                }

                .bottom-nav-item.active svg { transform: scale(1.1); }
                .bottom-nav-item:active svg { transform: scale(0.9); }

                .bottom-nav-label {
                    font-size: 0.62rem;
                    font-weight: 600;
                    letter-spacing: 0.02em;
                    line-height: 1;
                    opacity: 0;
                    transform: translateY(2px);
                    transition: opacity 0.2s, transform 0.2s;
                }

                .bottom-nav-item.active .bottom-nav-label {
                    opacity: 1;
                    transform: translateY(0);
                }

                /* Badge */
                .bottom-nav-badge {
                    position: absolute;
                    top: 0.4rem;
                    right: calc(50% - 1.2rem);
                    background: linear-gradient(135deg, #818cf8, #4f46e5);
                    color: white;
                    font-size: 0.58rem;
                    font-weight: 700;
                    line-height: 1;
                    padding: 0.15rem 0.32rem;
                    border-radius: 9999px;
                    min-width: 1rem;
                    text-align: center;
                    box-shadow: 0 2px 6px rgba(79,70,229,0.4);
                    border: 1.5px solid rgba(255,255,255,0.7);
                }

                /* Hide desktop hamburger */
                .mobile-header-toggle { display: none !important; }

                /* ============================================
                   BOTTOM SHEET — Mega Menu
                ============================================ */
                .bottom-sheet-backdrop {
                    position: fixed;
                    inset: 0;
                    background: rgba(0,0,0,0.4);
                    z-index: 60;
                    backdrop-filter: blur(4px);
                    -webkit-backdrop-filter: blur(4px);
                }

                .bottom-sheet {
                    position: fixed;
                    left: 0;
                    right: 0;
                    bottom: 5.5rem;
                    z-index: 61;
                    border-radius: 1.5rem 1.5rem 0 0;
                    max-height: 82vh;
                    display: flex;
                    flex-direction: column;
                    overflow: hidden;

                    /* Liquid glass sheet */
                    background: rgba(255,255,255,0.88);
                    backdrop-filter: blur(40px) saturate(200%);
                    -webkit-backdrop-filter: blur(40px) saturate(200%);
                    border: 1px solid rgba(255,255,255,0.6);
                    border-bottom: none;
                    box-shadow:
                        inset 0 1px 0 rgba(255,255,255,0.8),
                        0 -8px 40px rgba(0,0,0,0.15),
                        0 -2px 12px rgba(0,0,0,0.08);
                }

                @media (prefers-color-scheme: dark) {
                    .bottom-sheet {
                        background: rgba(18,18,28,0.82);
                        border-color: rgba(255,255,255,0.1);
                        box-shadow:
                            inset 0 1px 0 rgba(255,255,255,0.08),
                            0 -8px 40px rgba(0,0,0,0.5);
                    }
                }

                .sheet-handle {
                    width: 2.25rem;
                    height: 4px;
                    background: rgba(0,0,0,0.2);
                    border-radius: 9999px;
                    margin: 0.75rem auto 0;
                    flex-shrink: 0;
                    cursor: pointer;
                }

                @media (prefers-color-scheme: dark) {
                    .sheet-handle { background: rgba(255,255,255,0.2); }
                }

                /* Section headers */
                .sheet-section-title {
                    font-size: 0.7rem;
                    font-weight: 700;
                    letter-spacing: 0.08em;
                    text-transform: uppercase;
                    color: rgba(0,0,0,0.35);
                    padding: 1rem 1.25rem 0.5rem;
                }

                @media (prefers-color-scheme: dark) {
                    .sheet-section-title { color: rgba(255,255,255,0.3); }
                }

                /* Tabs — scrollables horizontalement */
                .sheet-tabs {
                    display: flex;
                    gap: 0.375rem;
                    padding: 0.75rem 1.25rem 0;
                    flex-shrink: 0;
                    overflow-x: auto;
                    scrollbar-width: none;
                    -webkit-overflow-scrolling: touch;
                    /* fade edges pour indiquer le scroll */
                    -webkit-mask-image: linear-gradient(to right, transparent 0%, black 1.25rem, black calc(100% - 1.25rem), transparent 100%);
                    mask-image: linear-gradient(to right, transparent 0%, black 1.25rem, black calc(100% - 1.25rem), transparent 100%);
                    padding-left: 1.25rem;
                    padding-right: 1.25rem;
                }

                .sheet-tabs::-webkit-scrollbar { display: none; }

                .sheet-tab {
                    flex-shrink: 0;
                    padding: 0.45rem 0.875rem;
                    font-size: 0.875rem;
                    font-weight: 600;
                    white-space: nowrap;
                    color: rgba(0,0,0,0.4);
                    background: rgba(0,0,0,0.05);
                    border: none;
                    border-radius: 9999px;
                    cursor: pointer;
                    transition: all 0.2s;
                    -webkit-tap-highlight-color: transparent;
                }

                .sheet-tab.active {
                    color: #3730a3;
                    background: rgba(99,102,241,0.12);
                    border: 1px solid rgba(99,102,241,0.2);
                    box-shadow: inset 0 1px 0 rgba(255,255,255,0.5);
                }

                @media (prefers-color-scheme: dark) {
                    .sheet-tab { color: rgba(255,255,255,0.35); background: rgba(255,255,255,0.06); }
                    .sheet-tab.active { color: #818cf8; background: rgba(99,102,241,0.18); border-color: rgba(99,102,241,0.3); }
                }

                /* Content scroll */
                .sheet-content {
                    overflow-y: auto;
                    flex: 1;
                    -webkit-overflow-scrolling: touch;
                    padding: 0.75rem 1.25rem 2rem;
                }

                /* Featured big cards (2-col) */
                .sheet-featured-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 0.625rem;
                    margin-bottom: 1.25rem;
                }

                .sheet-featured-card {
                    position: relative;
                    border-radius: 1rem;
                    overflow: hidden;
                    aspect-ratio: 1;
                    text-decoration: none;
                    display: block;
                    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
                }

                .sheet-featured-card img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                    display: block;
                    transition: transform 0.3s ease;
                }

                .sheet-featured-card:active img { transform: scale(1.04); }

                .sheet-featured-card-label {
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    padding: 1.25rem 0.75rem 0.75rem;
                    background: linear-gradient(to top, rgba(0,0,0,0.65), transparent);
                    color: #fff;
                    font-size: 0.8125rem;
                    font-weight: 700;
                }

                .sheet-featured-card-sub {
                    display: block;
                    font-size: 0.7rem;
                    font-weight: 400;
                    opacity: 0.8;
                    margin-top: 0.1rem;
                }

                /* List rows (links section) */
                .sheet-list {
                    border-radius: 1rem;
                    overflow: hidden;
                    margin-bottom: 1rem;
                    border: 1px solid rgba(0,0,0,0.06);
                    background: rgba(255,255,255,0.7);
                    backdrop-filter: blur(8px);
                }

                @media (prefers-color-scheme: dark) {
                    .sheet-list { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.06); }
                }

                .sheet-list-row {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 0.875rem 1rem;
                    text-decoration: none;
                    color: rgba(0,0,0,0.85);
                    font-size: 0.9375rem;
                    font-weight: 500;
                    border-bottom: 0.5px solid rgba(0,0,0,0.06);
                    transition: background 0.15s;
                }

                @media (prefers-color-scheme: dark) {
                    .sheet-list-row { color: rgba(255,255,255,0.85); border-bottom-color: rgba(255,255,255,0.06); }
                    .sheet-list-row:active { background: rgba(255,255,255,0.06); }
                }

                .sheet-list-row:last-child { border-bottom: none; }
                .sheet-list-row:active { background: rgba(0,0,0,0.04); }

                .sheet-list-row-chevron {
                    width: 0.875rem;
                    height: 0.875rem;
                    opacity: 0.3;
                    flex-shrink: 0;
                }

                .sheet-list-row-badge {
                    font-size: 0.7rem;
                    font-weight: 700;
                    padding: 0.2rem 0.5rem;
                    border-radius: 9999px;
                    background: rgba(99,102,241,0.12);
                    color: #4f46e5;
                    margin-right: 0.25rem;
                }

                /* Horizontal scroll chips */
                .sheet-chips {
                    display: flex;
                    gap: 0.5rem;
                    overflow-x: auto;
                    scrollbar-width: none;
                    padding-bottom: 0.25rem;
                    margin-bottom: 1rem;
                }

                .sheet-chips::-webkit-scrollbar { display: none; }

                .sheet-chip {
                    flex-shrink: 0;
                    padding: 0.4rem 0.875rem;
                    border-radius: 9999px;
                    font-size: 0.8125rem;
                    font-weight: 600;
                    text-decoration: none;
                    background: rgba(0,0,0,0.06);
                    color: rgba(0,0,0,0.7);
                    border: 1px solid rgba(0,0,0,0.06);
                    transition: all 0.15s;
                    white-space: nowrap;
                }

                .sheet-chip:active { background: rgba(99,102,241,0.15); color: #4f46e5; }

                .sheet-chip--new {
                    background: rgba(99,102,241,0.1);
                    color: #4338ca;
                    border-color: rgba(99,102,241,0.2);
                }

                .sheet-chip--sale {
                    background: rgba(239,68,68,0.08);
                    color: #dc2626;
                    border-color: rgba(239,68,68,0.15);
                }

                @media (prefers-color-scheme: dark) {
                    .sheet-chip { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.7); border-color: rgba(255,255,255,0.08); }
                    .sheet-chip--new { background: rgba(99,102,241,0.18); color: #818cf8; border-color: rgba(99,102,241,0.25); }
                    .sheet-chip--sale { background: rgba(239,68,68,0.12); color: #f87171; border-color: rgba(239,68,68,0.2); }
                }
            }
        </style>
    </head>
    <body
        class="min-h-screen bg-white dark:bg-zinc-800 antialiased"
        x-data="{ mobileMenuOpen: false, sheetOpen: false, sheetTab: '{{ ($rootCategories ?? collect())->first()?->slug ?? '' }}' }"
    >

      {{-- ========== BOTTOM SHEET MEGA MENU ========== --}}
      <div
        x-show="sheetOpen"
        x-transition:enter="transition-opacity ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-180"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="bottom-sheet-backdrop lg:hidden"
        @click="sheetOpen = false"
        style="display:none;" aria-hidden="true"
      ></div>

      <div
        x-show="sheetOpen"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-220 transform"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        class="bottom-sheet lg:hidden"
        role="dialog" aria-modal="true" aria-label="Catégories"
        style="display:none;"
      >
        <div class="sheet-handle" @click="sheetOpen = false"></div>

        {{--
            $rootCategories = catégories racines (parent_id = null), active, chargées avec leurs
            enfants actifs et leurs enfants featured (is_featured = true).

            Dans un View Composer (AppServiceProvider::boot) :

            View::composer('layouts.app', function ($view) {
                $view->with('rootCategories',
                    \App\Models\Category::active()
                        ->roots()
                        ->orderBy('sort_order')
                        ->with([
                            'children' => fn($q) => $q->active()->orderBy('sort_order'),
                            'children.children' => fn($q) => $q->active()->featured()->orderBy('sort_order'),
                        ])
                        ->get()
                );
            });
        --}}

        @php
            // Fallback si le View Composer n'est pas encore enregistré
            $rootCategories ??= \App\Models\Category::active()
                ->roots()
                ->orderBy('sort_order')
                ->with(['children' => fn($q) => $q->active()->orderBy('sort_order')])
                ->get();
        @endphp

        {{-- Tabs — une par catégorie racine --}}
        <div class="sheet-tabs">
          @foreach($rootCategories as $root)
            <button
              type="button"
              class="sheet-tab"
              :class="sheetTab === '{{ $root->slug }}' ? 'active' : ''"
              @click="sheetTab = '{{ $root->slug }}'"
            >{{ $root->name }}</button>
          @endforeach
        </div>

        <div class="sheet-content">

          @foreach($rootCategories as $loop_root => $root)
          <div
            x-show="sheetTab === '{{ $root->slug }}'"
            @if(!$loop->first) style="display:none;" @endif
          >

            {{--
                Featured : enfants directs avec is_featured = true ET une image.
                On prend les 4 premiers pour la grille 2×2.
            --}}
            @php
                $featured = $root->children->where('is_featured', true)->whereNotNull('image')->take(4);
            @endphp

            @if($featured->isNotEmpty())
            <p class="sheet-section-title">{{ __('Featured') }}</p>
            <div class="sheet-featured-grid">
              @foreach($featured as $feat)
              <a href="{{ route('categories.show', $feat->slug) }}" class="sheet-featured-card">
                <img
                  src="{{ asset('storage/' . $feat->image) }}"
                  alt="{{ $feat->name }}"
                  loading="lazy"
                >
                <div class="sheet-featured-card-label">
                  {{ $feat->name }}<span class="sheet-featured-card-sub">{{ __('Shop now') }}</span>
                </div>
              </a>
              @endforeach
            </div>
            @endif

            {{-- Sous-catégories : tous les enfants actifs en chips scrollables --}}
            @if($root->children->isNotEmpty())
            <p class="sheet-section-title">{{ __('Categories') }}</p>
            <div class="sheet-chips">
              @foreach($root->children->where('is_active', true)->sortBy('sort_order') as $child)
              <a
                href="#"
                class="sheet-chip"
              >{{ $child->name }}</a>
              @endforeach
              <a href="#', $root->slug) }}" class="sheet-chip">
                {{ __('Browse all') }} →
              </a>
            </div>
            @endif

          </div>
          @endforeach

        </div>
      </div>

      {{-- ========== PAGE ========== --}}
      <div class="bg-white">

        <div class="relative bg-gray-900">
          <div aria-hidden="true" class="absolute inset-0 overflow-hidden">
            <img src="https://tailwindui.com/plus-assets/img/ecommerce-images/home-page-01-hero-full-width.jpg" alt="" class="size-full object-cover">
          </div>
          <div aria-hidden="true" class="absolute inset-0 bg-gray-900 opacity-50"></div>

          <header class="relative z-10">
            <nav aria-label="Top">
              <div class="bg-gray-900">
                <div class="mx-auto flex h-10 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                  <form>
                    <div class="-ml-2 inline-grid grid-cols-1">
                      <select id="desktop-currency" name="currency" aria-label="Currency" class="col-start-1 row-start-1 w-full appearance-none rounded-md bg-gray-900 py-0.5 pr-7 pl-2 text-left text-base font-medium text-white focus:outline-2 focus:-outline-offset-1 focus:outline-white sm:text-sm/6">
                        <option>CAD</option><option>USD</option><option>AUD</option><option>EUR</option><option>GBP</option>
                      </select>
                      <svg class="pointer-events-none col-start-1 row-start-1 mr-1 size-5 self-center justify-self-end fill-gray-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
                    </div>
                  </form>
                  <div class="flex items-center space-x-6">
                    <a href="#" class="text-sm font-medium text-white hover:text-gray-100">Sign in</a>
                    <a href="#" class="text-sm font-medium text-white hover:text-gray-100">Create an account</a>
                  </div>
                </div>
              </div>

              <div class="bg-white/10 backdrop-blur-md backdrop-filter">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                  <div class="flex h-16 items-center justify-between">

                    <div class="hidden lg:flex lg:flex-1 lg:items-center">
                      <a href="#"><span class="sr-only">Your Company</span><img class="h-8 w-auto" src="https://tailwindui.com/plus-assets/img/logos/mark.svg?color=white" alt=""></a>
                    </div>

                    <div class="hidden h-full lg:flex">
                      <div class="inset-x-0 bottom-0 px-4">
                        <div class="flex h-full justify-center space-x-8">
                          {{-- Women --}}
                          <div class="flex" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                            <div class="relative flex">
                              <button type="button" @click="open = !open" class="relative z-10 flex items-center justify-center text-sm font-medium text-white transition-colors duration-200 ease-out" :aria-expanded="open">Women<span :class="open ? 'bg-white' : ''" class="absolute inset-x-0 -bottom-px h-0.5 transition duration-200 ease-out" aria-hidden="true"></span></button>
                            </div>
                            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-x-0 top-full text-sm text-gray-500" style="display:none;">
                              <div class="absolute inset-0 top-1/2 bg-white shadow-sm" aria-hidden="true"></div>
                              <div class="relative bg-white"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"><div class="grid grid-cols-4 gap-x-8 gap-y-10 py-16">
                                <div class="group relative"><img src="https://tailwindui.com/plus-assets/img/ecommerce-images/mega-menu-category-01.jpg" alt="" class="aspect-square w-full rounded-md bg-gray-100 object-cover group-hover:opacity-75"><a href="#" class="mt-4 block font-medium text-gray-900"><span class="absolute inset-0 z-10" aria-hidden="true"></span>New Arrivals</a><p aria-hidden="true" class="mt-1">Shop now</p></div>
                                <div class="group relative"><img src="https://tailwindui.com/plus-assets/img/ecommerce-images/mega-menu-category-02.jpg" alt="" class="aspect-square w-full rounded-md bg-gray-100 object-cover group-hover:opacity-75"><a href="#" class="mt-4 block font-medium text-gray-900"><span class="absolute inset-0 z-10" aria-hidden="true"></span>Basic Tees</a><p aria-hidden="true" class="mt-1">Shop now</p></div>
                                <div class="group relative"><img src="https://tailwindui.com/plus-assets/img/ecommerce-images/mega-menu-category-03.jpg" alt="" class="aspect-square w-full rounded-md bg-gray-100 object-cover group-hover:opacity-75"><a href="#" class="mt-4 block font-medium text-gray-900"><span class="absolute inset-0 z-10" aria-hidden="true"></span>Accessories</a><p aria-hidden="true" class="mt-1">Shop now</p></div>
                                <div class="group relative"><img src="https://tailwindui.com/plus-assets/img/ecommerce-images/mega-menu-category-04.jpg" alt="" class="aspect-square w-full rounded-md bg-gray-100 object-cover group-hover:opacity-75"><a href="#" class="mt-4 block font-medium text-gray-900"><span class="absolute inset-0 z-10" aria-hidden="true"></span>Carry</a><p aria-hidden="true" class="mt-1">Shop now</p></div>
                              </div></div></div>
                            </div>
                          </div>
                          {{-- Men --}}
                          <div class="flex" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                            <div class="relative flex">
                              <button type="button" @click="open = !open" class="relative z-10 flex items-center justify-center text-sm font-medium text-white transition-colors duration-200 ease-out" :aria-expanded="open">Men<span :class="open ? 'bg-white' : ''" class="absolute inset-x-0 -bottom-px h-0.5 transition duration-200 ease-out" aria-hidden="true"></span></button>
                            </div>
                            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-x-0 top-full text-sm text-gray-500" style="display:none;">
                              <div class="absolute inset-0 top-1/2 bg-white shadow-sm" aria-hidden="true"></div>
                              <div class="relative bg-white"><div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8"><div class="grid grid-cols-4 gap-x-8 gap-y-10 py-16">
                                <div class="group relative"><img src="https://tailwindui.com/plus-assets/img/ecommerce-images/mega-menu-01-men-category-01.jpg" alt="" class="aspect-square w-full rounded-md bg-gray-100 object-cover group-hover:opacity-75"><a href="#" class="mt-4 block font-medium text-gray-900"><span class="absolute inset-0 z-10" aria-hidden="true"></span>New Arrivals</a><p aria-hidden="true" class="mt-1">Shop now</p></div>
                                <div class="group relative"><img src="https://tailwindui.com/plus-assets/img/ecommerce-images/mega-menu-01-men-category-02.jpg" alt="" class="aspect-square w-full rounded-md bg-gray-100 object-cover group-hover:opacity-75"><a href="#" class="mt-4 block font-medium text-gray-900"><span class="absolute inset-0 z-10" aria-hidden="true"></span>Basic Tees</a><p aria-hidden="true" class="mt-1">Shop now</p></div>
                                <div class="group relative"><img src="https://tailwindui.com/plus-assets/img/ecommerce-images/mega-menu-01-men-category-03.jpg" alt="" class="aspect-square w-full rounded-md bg-gray-100 object-cover group-hover:opacity-75"><a href="#" class="mt-4 block font-medium text-gray-900"><span class="absolute inset-0 z-10" aria-hidden="true"></span>Accessories</a><p aria-hidden="true" class="mt-1">Shop now</p></div>
                                <div class="group relative"><img src="https://tailwindui.com/plus-assets/img/ecommerce-images/mega-menu-01-men-category-04.jpg" alt="" class="aspect-square w-full rounded-md bg-gray-100 object-cover group-hover:opacity-75"><a href="#" class="mt-4 block font-medium text-gray-900"><span class="absolute inset-0 z-10" aria-hidden="true"></span>Carry</a><p aria-hidden="true" class="mt-1">Shop now</p></div>
                              </div></div></div>
                            </div>
                          </div>
                          <a href="#" class="flex items-center text-sm font-medium text-white">Company</a>
                          <a href="#" class="flex items-center text-sm font-medium text-white">Stores</a>
                        </div>
                      </div>
                    </div>

                    <div class="flex flex-1 items-center lg:hidden">
                      <button type="button" class="mobile-header-toggle -ml-2 p-2 text-white">
                        <span class="sr-only">Ouvrir le menu</span>
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                      </button>
                      <a href="#" class="ml-2 p-2 text-white"><span class="sr-only">Rechercher</span><svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg></a>
                    </div>

                    <a href="#" class="lg:hidden"><span class="sr-only">Your Company</span><img src="https://tailwindui.com/plus-assets/img/logos/mark.svg?color=white" alt="" class="h-8 w-auto"></a>

                    <div class="flex flex-1 items-center justify-end">
                      <a href="#" class="hidden text-sm font-medium text-white lg:block">Search</a>
                      <div class="flex items-center lg:ml-8">
                        <a href="#" class="p-2 text-white lg:hidden"><span class="sr-only">Help</span><svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" /></svg></a>
                        <a href="#" class="hidden text-sm font-medium text-white lg:block">Help</a>
                        <div class="ml-4 flow-root lg:ml-8">
                          <a href="#" class="group -m-2 flex items-center p-2">
                            <svg class="size-6 shrink-0 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                            <span class="ml-2 text-sm font-medium text-white">0</span>
                            <span class="sr-only">items in cart, view bag</span>
                          </a>
                        </div>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </nav>
          </header>

          <div class="relative mx-auto flex max-w-3xl flex-col items-center px-6 py-32 text-center sm:py-64 lg:px-0">
            <h1 class="text-4xl font-bold tracking-tight text-white lg:text-6xl">New arrivals are here</h1>
            <p class="mt-4 text-xl text-white">The new arrivals have, well, newly arrived. Check out the latest options from our summer small-batch release while they're still in stock.</p>
            <a href="#" class="mt-8 inline-block rounded-md border border-transparent bg-white px-8 py-3 text-base font-medium text-gray-900 hover:bg-gray-100">Shop New Arrivals</a>
          </div>
        </div>

        <main>

            <livewire:shared::collections.page />

          <section aria-labelledby="social-impact-heading" class="mx-auto max-w-7xl px-4 pt-24 sm:px-6 sm:pt-32 lg:px-8">
            <div class="relative overflow-hidden rounded-lg">
              <div class="absolute inset-0"><img src="https://tailwindui.com/plus-assets/img/ecommerce-images/home-page-01-feature-section-01.jpg" alt="" class="size-full object-cover"></div>
              <div class="relative bg-gray-900/75 px-6 py-32 sm:px-12 sm:py-40 lg:px-16">
                <div class="relative mx-auto flex max-w-3xl flex-col items-center text-center">
                  <h2 id="social-impact-heading" class="text-3xl font-bold tracking-tight text-white sm:text-4xl"><span class="block sm:inline">Level up</span> <span class="block sm:inline">your desk</span></h2>
                  <p class="mt-3 text-xl text-white">Make your desk beautiful and organized. Post a picture to social media and watch it get more likes than life-changing announcements.</p>
                  <a href="#" class="mt-8 block w-full rounded-md border border-transparent bg-white px-8 py-3 text-base font-medium text-gray-900 hover:bg-gray-100 sm:w-auto">Shop Workspace</a>
                </div>
              </div>
            </div>
          </section>

          <section aria-labelledby="collection-heading" class="mx-auto max-w-xl px-4 pt-24 sm:px-6 sm:pt-32 lg:max-w-7xl lg:px-8">
            <h2 id="collection-heading" class="text-2xl font-bold tracking-tight text-gray-900">Shop by Collection</h2>
            <p class="mt-4 text-base text-gray-500">Each season, we collaborate with world-class designers to create a collection inspired by the natural world.</p>
            <div class="mt-10 space-y-12 lg:grid lg:grid-cols-3 lg:gap-x-8 lg:space-y-0">
              <a href="#" class="group block"><img src="https://tailwindui.com/plus-assets/img/ecommerce-images/home-page-01-collection-01.jpg" alt="" class="aspect-3/2 w-full rounded-lg object-cover group-hover:opacity-75 lg:aspect-5/6"><h3 class="mt-4 text-base font-semibold text-gray-900">Handcrafted Collection</h3><p class="mt-2 text-sm text-gray-500">Keep your phone, keys, and wallet together, so you can lose everything at once.</p></a>
              <a href="#" class="group block"><img src="https://tailwindui.com/plus-assets/img/ecommerce-images/home-page-01-collection-02.jpg" alt="" class="aspect-3/2 w-full rounded-lg object-cover group-hover:opacity-75 lg:aspect-5/6"><h3 class="mt-4 text-base font-semibold text-gray-900">Organized Desk Collection</h3><p class="mt-2 text-sm text-gray-500">The rest of the house will still be a mess, but your desk will look great.</p></a>
              <a href="#" class="group block"><img src="https://tailwindui.com/plus-assets/img/ecommerce-images/home-page-01-collection-03.jpg" alt="" class="aspect-3/2 w-full rounded-lg object-cover group-hover:opacity-75 lg:aspect-5/6"><h3 class="mt-4 text-base font-semibold text-gray-900">Focus Collection</h3><p class="mt-2 text-sm text-gray-500">Be more productive than enterprise project managers with a single piece of paper.</p></a>
            </div>
          </section>

          <section aria-labelledby="comfort-heading" class="mx-auto max-w-7xl px-4 py-24 sm:px-6 sm:py-32 lg:px-8">
            <div class="relative overflow-hidden rounded-lg">
              <div class="absolute inset-0"><img src="https://tailwindui.com/plus-assets/img/ecommerce-images/home-page-01-feature-section-02.jpg" alt="" class="size-full object-cover"></div>
              <div class="relative bg-gray-900/75 px-6 py-32 sm:px-12 sm:py-40 lg:px-16">
                <div class="relative mx-auto flex max-w-3xl flex-col items-center text-center">
                  <h2 id="comfort-heading" class="text-3xl font-bold tracking-tight text-white sm:text-4xl">Simple productivity</h2>
                  <p class="mt-3 text-xl text-white">Endless tasks, limited hours, a single piece of paper. Just the undeniable urge to fill empty circles.</p>
                  <a href="#" class="mt-8 block w-full rounded-md border border-transparent bg-white px-8 py-3 text-base font-medium text-gray-900 hover:bg-gray-100 sm:w-auto">Shop Focus</a>
                </div>
              </div>
            </div>
          </section>
        </main>

        <footer aria-labelledby="footer-heading" class="bg-gray-900">
          <h2 id="footer-heading" class="sr-only">Footer</h2>
          <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="py-20 xl:grid xl:grid-cols-3 xl:gap-8">
              <div class="grid grid-cols-2 gap-8 xl:col-span-2">
                <div class="space-y-12 md:grid md:grid-cols-2 md:gap-8 md:space-y-0">
                  <div><h3 class="text-sm font-medium text-white">Shop</h3><ul role="list" class="mt-6 space-y-6"><li class="text-sm"><a href="#" class="text-gray-300 hover:text-white">Bags</a></li><li class="text-sm"><a href="#" class="text-gray-300 hover:text-white">Tees</a></li><li class="text-sm"><a href="#" class="text-gray-300 hover:text-white">Objects</a></li><li class="text-sm"><a href="#" class="text-gray-300 hover:text-white">Home Goods</a></li><li class="text-sm"><a href="#" class="text-gray-300 hover:text-white">Accessories</a></li></ul></div>
                  <div><h3 class="text-sm font-medium text-white">Company</h3><ul role="list" class="mt-6 space-y-6"><li class="text-sm"><a href="#" class="text-gray-300 hover:text-white">Who we are</a></li><li class="text-sm"><a href="#" class="text-gray-300 hover:text-white">Sustainability</a></li><li class="text-sm"><a href="#" class="text-gray-300 hover:text-white">Press</a></li><li class="text-sm"><a href="#" class="text-gray-300 hover:text-white">Careers</a></li></ul></div>
                </div>
                <div class="space-y-12 md:grid md:grid-cols-2 md:gap-8 md:space-y-0">
                  <div><h3 class="text-sm font-medium text-white">Account</h3><ul role="list" class="mt-6 space-y-6"><li class="text-sm"><a href="#" class="text-gray-300 hover:text-white">Manage Account</a></li><li class="text-sm"><a href="#" class="text-gray-300 hover:text-white">Returns &amp; Exchanges</a></li></ul></div>
                  <div><h3 class="text-sm font-medium text-white">Connect</h3><ul role="list" class="mt-6 space-y-6"><li class="text-sm"><a href="#" class="text-gray-300 hover:text-white">Contact Us</a></li><li class="text-sm"><a href="#" class="text-gray-300 hover:text-white">Instagram</a></li></ul></div>
                </div>
              </div>
              <div class="mt-12 md:mt-16 xl:mt-0">
                <h3 class="text-sm font-medium text-white">Newsletter</h3>
                <form class="mt-4 flex sm:max-w-md">
                  <input type="text" autocomplete="email" required aria-label="Email address" class="block w-full rounded-md bg-white px-4 py-2 text-base text-gray-900">
                  <div class="ml-4 shrink-0"><button type="submit" class="flex w-full items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white hover:bg-indigo-700">Sign up</button></div>
                </form>
              </div>
            </div>
            <div class="border-t border-gray-800 py-10"><p class="text-sm text-gray-400">Copyright &copy; 2021 Your Company, Inc.</p></div>
          </div>
        </footer>

      </div>

      {{-- ========== BOTTOM NAV — iOS 26 Liquid Glass ========== --}}
      <nav class="bottom-nav" aria-label="Navigation mobile">

        <a href="#" class="bottom-nav-item active" aria-label="Accueil">
          <svg fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
          <span class="bottom-nav-label">Home</span>
        </a>

        <button
          type="button"
          class="bottom-nav-item"
          :class="sheetOpen ? 'active' : ''"
          aria-label="Catégories"
          @click="sheetOpen = !sheetOpen"
        >
          <svg fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" /></svg>
          <span class="bottom-nav-label">Shop</span>
        </button>

        <a href="#" class="bottom-nav-item" aria-label="Rechercher">
          <svg fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
          <span class="bottom-nav-label">Search</span>
        </a>

        <a href="#" class="bottom-nav-item" aria-label="Panier">
          <span class="bottom-nav-badge">3</span>
          <svg fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z" /></svg>
          <span class="bottom-nav-label">Bag</span>
        </a>

        <a href="#" class="bottom-nav-item" aria-label="Mon compte">
          <svg fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
          <span class="bottom-nav-label">Account</span>
        </a>

      </nav>

    @fluxScripts
    </body>
</html>
