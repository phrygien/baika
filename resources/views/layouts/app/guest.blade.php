<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')

        {{-- InstantSearch.js + Typesense adapter --}}
        <script src="https://cdn.jsdelivr.net/npm/typesense-instantsearch-adapter@2/dist/typesense-instantsearch-adapter.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/instantsearch.js@4/dist/instantsearch.production.min.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/instantsearch.css@7/themes/reset-min.css" />

        <style>
            /* ── Search modal overlay ── */
            #search-modal {
                display: none;
                position: fixed;
                inset: 0;
                z-index: 9999;
                background: rgba(0,0,0,0.5);
                backdrop-filter: blur(4px);
                -webkit-backdrop-filter: blur(4px);
                align-items: flex-start;
                justify-content: center;
                padding-top: 80px;
            }
            #search-modal.open { display: flex; }

            #search-box-wrapper {
                width: 100%;
                max-width: 680px;
                margin: 0 1rem;
                background: white;
                border-radius: 1rem;
                box-shadow: 0 24px 64px rgba(0,0,0,0.25);
                overflow: hidden;
                max-height: calc(100vh - 120px);
                display: flex;
                flex-direction: column;
            }

            .dark #search-box-wrapper {
                background: rgb(39 39 42);
                border: 1px solid rgb(63 63 70);
            }

            /* Search input zone */
            #search-box-wrapper .ais-SearchBox {
                flex-shrink: 0;
                padding: 1rem;
                border-bottom: 1px solid rgb(228 228 231);
            }
            .dark #search-box-wrapper .ais-SearchBox {
                border-bottom-color: rgb(63 63 70);
            }

            #search-box-wrapper .ais-SearchBox-form {
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }

            #search-box-wrapper .ais-SearchBox-input {
                flex: 1;
                border: none;
                outline: none;
                font-size: 1.125rem;
                background: transparent;
                color: rgb(24 24 27);
                font-weight: 500;
            }
            .dark #search-box-wrapper .ais-SearchBox-input {
                color: rgb(244 244 245);
            }
            #search-box-wrapper .ais-SearchBox-input::placeholder { color: rgb(161 161 170); }

            #search-box-wrapper .ais-SearchBox-submit,
            #search-box-wrapper .ais-SearchBox-reset {
                background: none;
                border: none;
                cursor: pointer;
                color: rgb(113 113 122);
                display: flex;
                align-items: center;
            }
            #search-box-wrapper .ais-SearchBox-submit { order: -1; }
            #search-box-wrapper .ais-SearchBox-submitIcon,
            #search-box-wrapper .ais-SearchBox-resetIcon { width: 1.25rem; height: 1.25rem; fill: currentColor; }

            /* Results zone */
            #search-results {
                overflow-y: auto;
                flex: 1;
                padding: 0.75rem;
            }

            /* Hits grid */
            #search-results .ais-Hits-list {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0.5rem;
                list-style: none;
                margin: 0;
                padding: 0;
            }

            @media (min-width: 480px) {
                #search-results .ais-Hits-list {
                    grid-template-columns: 1fr 1fr 1fr;
                }
            }

            /* Hit card */
            .hit-card {
                display: flex;
                gap: 0.625rem;
                align-items: center;
                padding: 0.5rem;
                border-radius: 0.625rem;
                text-decoration: none;
                transition: background 0.12s;
                cursor: pointer;
            }

            .hit-card:hover { background: rgb(244 244 245); }
            .dark .hit-card:hover { background: rgb(63 63 70); }

            .hit-img {
                width: 3rem;
                height: 3rem;
                border-radius: 0.5rem;
                object-fit: cover;
                flex-shrink: 0;
                background: rgb(228 228 231);
            }
            .dark .hit-img { background: rgb(63 63 70); }

            .hit-placeholder {
                width: 3rem;
                height: 3rem;
                border-radius: 0.5rem;
                flex-shrink: 0;
                background: rgb(228 228 231);
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .dark .hit-placeholder { background: rgb(63 63 70); }

            .hit-info { min-width: 0; flex: 1; }

            .hit-name {
                font-size: 0.8125rem;
                font-weight: 600;
                color: rgb(24 24 27);
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
                line-height: 1.3;
            }
            .dark .hit-name { color: rgb(244 244 245); }

            .hit-price {
                margin-top: 0.2rem;
                font-size: 0.8125rem;
                font-weight: 700;
                color: rgb(79 70 229);
            }

            .hit-meta {
                margin-top: 0.1rem;
                font-size: 0.7rem;
                color: rgb(113 113 122);
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            /* Highlight */
            .ais-Highlight-highlighted {
                background: rgb(221 214 254);
                color: rgb(79 70 229);
                border-radius: 2px;
                font-style: normal;
            }
            .dark .ais-Highlight-highlighted {
                background: rgba(99,102,241,0.3);
                color: rgb(165 180 252);
            }

            /* Stats + empty */
            #search-stats {
                flex-shrink: 0;
                padding: 0.5rem 1rem;
                border-top: 1px solid rgb(228 228 231);
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
            }
            .dark #search-stats { border-top-color: rgb(63 63 70); }

            #search-stats .ais-Stats-text {
                font-size: 0.75rem;
                color: rgb(113 113 122);
            }

            .ais-Hits--empty { padding: 2rem; text-align: center; color: rgb(113 113 122); font-size: 0.875rem; }

            /* Empty state initial (avant recherche) */
            #search-empty-state {
                padding: 2.5rem 1rem;
                text-align: center;
                color: rgb(113 113 122);
            }
            .dark #search-empty-state { color: rgb(113 113 122); }
        </style>
    </head>

    <body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">

        {{-- ══════════════════════════════════════════
             SEARCH MODAL
        ═══════════════════════════════════════════════ --}}
        <div id="search-modal" role="dialog" aria-modal="true" aria-label="{{ __('Product search') }}">
            <div id="search-box-wrapper">
                {{-- InstantSearch widgets montés ici par JS --}}
                <div id="searchbox"></div>

                <div id="search-results">
                    <div id="hits"></div>
                    <div id="search-empty-state">
                        <svg class="mx-auto mb-3 size-10 opacity-30" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                        </svg>
                        <p class="text-sm font-medium">{{ __('Type to search products...') }}</p>
                        <p class="mt-1 text-xs opacity-60">{{ __('Search by name, SKU, brand or category') }}</p>
                    </div>
                </div>

                <div id="search-stats">
                    <div id="stats"></div>
                    <button
                        type="button"
                        onclick="closeSearch()"
                        class="text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                    >
                        {{ __('Press') }} <kbd class="rounded border border-zinc-300 bg-zinc-100 px-1 py-0.5 font-mono text-xs dark:border-zinc-600 dark:bg-zinc-800">Esc</kbd> {{ __('to close') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════
             FLUX HEADER
        ═══════════════════════════════════════════════ --}}
        <flux:header container class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">

            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:brand href="{{ route('home') }}" name="{{ config('app.name') }}" class="max-lg:hidden dark:hidden" />
            <flux:brand href="{{ route('home') }}" name="{{ config('app.name') }}" class="max-lg:hidden! hidden dark:flex" />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="home" href="{{ route('home') }}" wire:navigate
                    :current="request()->routeIs('home')">
                    {{ __('Home') }}
                </flux:navbar.item>
                <flux:navbar.item icon="cube" href="{{ route('products') }}" wire:navigate
                    :current="request()->routeIs('products*')">
                    {{ __('Products') }}
                </flux:navbar.item>
                <flux:navbar.item icon="inbox" href="#">{{ __('Orders') }}</flux:navbar.item>
                <flux:navbar.item icon="users" href="#">{{ __('Customers') }}</flux:navbar.item>

                <flux:separator vertical variant="subtle" class="my-2" />

                <flux:dropdown class="max-lg:hidden">
                    <flux:navbar.item icon:trailing="chevron-down">{{ __('More') }}</flux:navbar.item>
                    <flux:navmenu>
                        <flux:navmenu.item href="#" icon="tag">{{ __('Categories') }}</flux:navmenu.item>
                        <flux:navmenu.item href="#" icon="building-storefront">{{ __('Suppliers') }}</flux:navmenu.item>
                        <flux:navmenu.item href="#" icon="chart-bar">{{ __('Analytics') }}</flux:navmenu.item>
                    </flux:navmenu>
                </flux:dropdown>
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="me-4">
                {{-- Bouton search — ouvre la modale InstantSearch --}}
                <flux:navbar.item
                    icon="magnifying-glass"
                    label="{{ __('Search') }}"
                    onclick="openSearch()"
                    class="cursor-pointer"
                />
                <flux:navbar.item class="max-lg:hidden" icon="cog-6-tooth" href="#" label="{{ __('Settings') }}" />
                <flux:navbar.item class="max-lg:hidden" icon="information-circle" href="#" label="{{ __('Help') }}" />
            </flux:navbar>

            <flux:dropdown position="top" align="start">
                <flux:profile avatar="https://fluxui.dev/img/demo/user.png" />
                <flux:menu>
                    <flux:menu.radio.group>
                        <flux:menu.radio checked>{{ auth()->user()?->name ?? 'Guest' }}</flux:menu.radio>
                    </flux:menu.radio.group>
                    <flux:menu.separator />
                    <flux:menu.item icon="user-circle" href="#">{{ __('Profile') }}</flux:menu.item>
                    <flux:menu.item icon="cog-6-tooth" href="#">{{ __('Settings') }}</flux:menu.item>
                    <flux:menu.separator />
                    <flux:menu.item icon="arrow-right-start-on-rectangle"
                        onclick="document.getElementById('logout-form').submit()">
                        {{ __('Logout') }}
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{-- Hidden logout form --}}
        <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
            @csrf
        </form>

        {{-- ══════════════════════════════════════════
             FLUX SIDEBAR (mobile)
        ═══════════════════════════════════════════════ --}}
        <flux:sidebar sticky collapsible="mobile" class="lg:hidden bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
            <flux:sidebar.header>
                <flux:sidebar.brand
                    href="{{ route('home') }}"
                    name="{{ config('app.name') }}"
                />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item icon="home" href="{{ route('home') }}" wire:navigate
                    :current="request()->routeIs('home')">{{ __('Home') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="cube" href="{{ route('products') }}" wire:navigate
                    :current="request()->routeIs('products*')">{{ __('Products') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="inbox" href="#">{{ __('Orders') }}</flux:sidebar.item>
                <flux:sidebar.item icon="users" href="#">{{ __('Customers') }}</flux:sidebar.item>

                <flux:sidebar.group expandable heading="{{ __('Catalog') }}" class="grid">
                    <flux:sidebar.item icon="tag" href="#">{{ __('Categories') }}</flux:sidebar.item>
                    <flux:sidebar.item icon="building-storefront" href="#">{{ __('Suppliers') }}</flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="magnifying-glass" onclick="openSearch()">{{ __('Search') }}</flux:sidebar.item>
                <flux:sidebar.item icon="cog-6-tooth" href="#">{{ __('Settings') }}</flux:sidebar.item>
                <flux:sidebar.item icon="information-circle" href="#">{{ __('Help') }}</flux:sidebar.item>
            </flux:sidebar.nav>
        </flux:sidebar>

        {{-- ══════════════════════════════════════════
             MAIN CONTENT
        ═══════════════════════════════════════════════ --}}
        <flux:main>
            {{ $slot }}
        </flux:main>

        @fluxScripts
        <x-notification position="top-center" />

        {{-- ══════════════════════════════════════════
             INSTANTSEARCH.JS + TYPESENSE
        ═══════════════════════════════════════════════ --}}
        <script>
            // ── Config Typesense ────────────────────────────────────────────
            const typesenseConfig = {
                apiKey:  '{{ config('scout.typesense.client-settings.api_key') }}',
                nodes: [{
                    host:     '{{ parse_url(config('scout.typesense.client-settings.nodes.0.url', 'http://localhost'), PHP_URL_HOST) ?? 'localhost' }}',
                    port:     {{ config('scout.typesense.client-settings.nodes.0.port', 8108) }},
                    protocol: '{{ config('scout.typesense.client-settings.nodes.0.protocol', 'http') }}',
                }],
                connectionTimeoutSeconds: 2,
            };

            const typesenseAdapter = new TypesenseInstantSearchAdapter({
                server: typesenseConfig,
                additionalSearchParameters: {
                    query_by: 'name,sku,brand_name,category_name,short_description',
                    query_by_weights: '10,8,4,3,2',
                    filter_by: 'status:=approved && is_active:=true',
                    sort_by: 'total_sold:desc',
                    highlight_fields: 'name,brand_name,category_name',
                    per_page: 12,
                    num_typos: 1,
                },
            });

            const searchClient = typesenseAdapter.searchClient;

            const search = instantsearch({
                indexName: '{{ (new \App\Models\Product)->searchableAs() }}',
                searchClient,
                future: { preserveSharedStateOnUnmount: true },
            });

            search.addWidgets([
                // Search box
                instantsearch.widgets.searchBox({
                    container: '#searchbox',
                    placeholder: '{{ __('Search products, brands, categories...') }}',
                    autofocus: true,
                    showSubmit: true,
                    showReset: true,
                    queryHook(query, search) {
                        const empty = document.getElementById('search-empty-state');
                        if (query.trim() === '') {
                            empty.style.display = 'block';
                            document.getElementById('hits').style.display = 'none';
                        } else {
                            empty.style.display = 'none';
                            document.getElementById('hits').style.display = 'block';
                        }
                        search(query);
                    },
                }),

                // Hits
                instantsearch.widgets.hits({
                    container: '#hits',
                    templates: {
                        item(hit, { html, components }) {
                            const price   = hit.base_price ? parseFloat(hit.base_price).toFixed(2) : null;
                            const compare = hit.compare_at_price ? parseFloat(hit.compare_at_price).toFixed(2) : null;
                            const currency = hit.currency || 'USD';
                            const discount = (compare && parseFloat(compare) > parseFloat(price))
                                ? Math.round((1 - parseFloat(price) / parseFloat(compare)) * 100)
                                : 0;

                            return html`
                                <a href="#" class="hit-card">
                                    ${hit.image_path
                                        ? html`<img class="hit-img" src="${hit.image_path}" alt="${hit.name}" loading="lazy" />`
                                        : html`<div class="hit-placeholder">
                                            <svg style="width:1.25rem;height:1.25rem;color:rgb(161,161,170)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z" />
                                            </svg>
                                        </div>`
                                    }
                                    <div class="hit-info">
                                        <div class="hit-name">
                                            ${components.Highlight({ hit, attribute: 'name' })}
                                        </div>
                                        ${price ? html`
                                            <div class="hit-price">
                                                ${price} <span style="font-weight:400;font-size:0.7rem;color:rgb(113,113,122)">${currency}</span>
                                                ${discount > 0 ? html`<span style="font-size:0.68rem;color:rgb(239,68,68);margin-left:0.25rem">-${discount}%</span>` : ''}
                                            </div>
                                        ` : ''}
                                        <div class="hit-meta">
                                            ${hit.category_name || ''}${hit.category_name && hit.brand_name ? ' · ' : ''}${hit.brand_name || ''}
                                        </div>
                                    </div>
                                </a>
                            `;
                        },
                        empty(results, { html }) {
                            return html`
                                <div style="padding:2rem;text-align:center;color:rgb(113,113,122)">
                                    <p style="font-size:0.875rem;font-weight:500">
                                        {{ __('No results for') }} « ${results.query} »
                                    </p>
                                    <p style="font-size:0.75rem;margin-top:0.25rem">
                                        {{ __('Try a different search term') }}
                                    </p>
                                </div>
                            `;
                        },
                    },
                }),

                // Stats
                instantsearch.widgets.stats({
                    container: '#stats',
                    templates: {
                        text(data, { html }) {
                            if (data.query === '') return html``;
                            return html`
                                <span>
                                    ${data.nbHits.toLocaleString()} {{ __('result') }}${data.nbHits !== 1 ? 's' : ''}
                                    {{ __('in') }} ${data.processingTimeMS}ms
                                    <span style="margin-left:0.375rem;background:rgb(254,243,199);color:rgb(146,64,14);font-size:0.65rem;font-weight:700;padding:0.1rem 0.4rem;border-radius:9999px">⚡ Typesense</span>
                                </span>
                            `;
                        },
                    },
                }),
            ]);

            search.start();

            // ── Modal open / close ──────────────────────────────────────────
            function openSearch() {
                document.getElementById('search-modal').classList.add('open');
                document.body.style.overflow = 'hidden';
                // Focus le searchbox après l'animation
                setTimeout(() => {
                    const input = document.querySelector('#searchbox input');
                    if (input) input.focus();
                }, 50);
            }

            function closeSearch() {
                document.getElementById('search-modal').classList.remove('open');
                document.body.style.overflow = '';
            }

            // Fermer sur backdrop click
            document.getElementById('search-modal').addEventListener('click', function(e) {
                if (e.target === this) closeSearch();
            });

            // Fermer sur Escape / ouvrir sur Ctrl+K / Cmd+K
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeSearch();
                }
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    e.preventDefault();
                    const modal = document.getElementById('search-modal');
                    modal.classList.contains('open') ? closeSearch() : openSearch();
                }
            });
        </script>
    </body>
</html>
