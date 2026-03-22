<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')

        {{-- InstantSearch.js + Typesense adapter --}}
        <script src="https://cdn.jsdelivr.net/npm/typesense-instantsearch-adapter@2/dist/typesense-instantsearch-adapter.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/instantsearch.js@4/dist/instantsearch.production.min.js"></script>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/instantsearch.css@7/themes/reset-min.css" />

        <style>
            /* ════════════════════════════════════════════
               BASE
            ════════════════════════════════════════════ */
            body { padding-top: 0 !important; }

            /* ════════════════════════════════════════════
               HEADER WRAPPER
            ════════════════════════════════════════════ */
            #amz-header {
                position: sticky;
                top: 0;
                z-index: 1000;
                width: 100%;
                font-family: Arial, sans-serif;
            }

            /* ════════════════════════════════════════════
               TOP BAR
            ════════════════════════════════════════════ */
            #amz-topbar {
                background: #131921;
                display: flex;
                align-items: stretch;
                padding: 0 0.5rem;
                min-height: 60px;
                gap: 0;
            }

            .amz-block {
                display: flex;
                flex-direction: column;
                justify-content: center;
                padding: 0.35rem 0.6rem;
                cursor: pointer;
                border: 2px solid transparent;
                border-radius: 2px;
                transition: border-color 0.1s;
                text-decoration: none;
                white-space: nowrap;
            }
            .amz-block:hover { border-color: #fff; }
            .amz-block .amz-label { font-size: 0.68rem; color: #ccc; line-height: 1.2; }
            .amz-block .amz-value { font-size: 0.8125rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 0.2rem; }

            /* Logo */
            #amz-logo {
                display: flex; align-items: center;
                padding: 0.5rem 0.75rem 0.5rem 0.25rem;
                border: 2px solid transparent; border-radius: 2px;
                cursor: pointer; flex-shrink: 0; text-decoration: none;
            }
            #amz-logo:hover { border-color: #fff; }

            /* Delivery */
            #amz-delivery { flex-shrink: 0; }
            #amz-delivery .amz-label { font-size: 0.68rem; color: #ccc; line-height: 1; }
            #amz-delivery .amz-value { font-size: 0.8125rem; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 0.2rem; }

            /* ════════════════════════════════════════════
               SEARCH BAR — InstantSearch inline + dropdown
            ════════════════════════════════════════════ */
            #amz-search-wrapper {
                flex: 1;
                position: relative;
                display: flex;
                align-items: center;
                margin: 0.5rem 0.75rem;
                min-width: 0;
            }

            /* IS SearchBox fills available space */
            #amz-search-wrapper .ais-SearchBox { flex: 1; min-width: 0; }

            #amz-search-wrapper .ais-SearchBox-form {
                display: flex;
                align-items: center;
                height: 40px;
            }

            /* Category select */
            #amz-search-category {
                background: #e3e6e6;
                border: none;
                height: 40px;
                padding: 0 0.5rem;
                font-size: 0.75rem;
                color: #333;
                cursor: pointer;
                border-right: 1px solid #cdcdcd;
                border-radius: 4px 0 0 4px;
                min-width: 60px;
                max-width: 90px;
                appearance: none;
                -webkit-appearance: none;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23555' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
                background-repeat: no-repeat;
                background-position: right 6px center;
                padding-right: 20px;
                flex-shrink: 0;
            }

            /* IS input styled as Amazon */
            #amz-search-wrapper .ais-SearchBox-input {
                flex: 1;
                border: none;
                outline: none;
                padding: 0 0.75rem;
                font-size: 0.9375rem;
                height: 40px;
                background: #fff;
                color: #111;
                min-width: 0;
            }
            #amz-search-wrapper .ais-SearchBox-input::placeholder { color: #999; }

            /* Hide IS default submit — keep reset (x button) */
            #amz-search-wrapper .ais-SearchBox-submit { display: none; }
            #amz-search-wrapper .ais-SearchBox-reset {
                background: #fff;
                border: none;
                height: 40px;
                padding: 0 0.5rem;
                cursor: pointer;
                display: flex;
                align-items: center;
                color: #71717a;
            }
            #amz-search-wrapper .ais-SearchBox-resetIcon { width: 0.875rem; height: 0.875rem; fill: currentColor; }

            /* Orange search button */
            #amz-search-btn {
                background: #febd69;
                border: none;
                height: 40px;
                width: 45px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                border-radius: 0 4px 4px 0;
                flex-shrink: 0;
                transition: background 0.12s;
            }
            #amz-search-btn:hover { background: #f3a847; }
            #amz-search-btn svg { width: 1.1rem; height: 1.1rem; color: #111; }

            /* Focus ring on the bar */
            #amz-search-wrapper:focus-within #amz-search-category,
            #amz-search-wrapper:focus-within .ais-SearchBox-input,
            #amz-search-wrapper:focus-within .ais-SearchBox-reset,
            #amz-search-wrapper:focus-within #amz-search-btn {
                outline: none;
            }
            #amz-search-wrapper:focus-within {
                box-shadow: 0 0 0 3px #f08804;
                border-radius: 4px;
            }

            /* ════════════════════════════════════════════
               DROPDOWN PANEL
            ════════════════════════════════════════════ */
            #search-dropdown {
                position: absolute;
                top: calc(100% + 6px);
                left: 0;
                right: 0;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 8px 40px rgba(0,0,0,0.18);
                overflow: hidden;
                max-height: 500px;
                display: none;         /* toggled via JS */
                flex-direction: column;
                z-index: 9999;
                border: 1px solid #e4e4e7;
            }
            #search-dropdown.open { display: flex; }
            .dark #search-dropdown { background: #1e1e2e; border-color: #333; }

            /* Scroll area */
            #search-dropdown-results {
                overflow-y: auto;
                flex: 1;
                padding: 0.625rem;
            }

            /* Hits grid — 2 cols mobile, 3 tablet, 4 desktop */
            #search-dropdown-results .ais-Hits-list {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 0.375rem;
                list-style: none; margin: 0; padding: 0;
            }
            @media (min-width: 600px)  { #search-dropdown-results .ais-Hits-list { grid-template-columns: repeat(3, 1fr); } }
            @media (min-width: 900px)  { #search-dropdown-results .ais-Hits-list { grid-template-columns: repeat(4, 1fr); } }

            /* Hit card */
            .hit-card {
                display: flex; gap: 0.5rem; align-items: center;
                padding: 0.45rem; border-radius: 6px;
                text-decoration: none; transition: background 0.1s; cursor: pointer;
            }
            .hit-card:hover { background: #f5f5f5; }
            .dark .hit-card:hover { background: #2a2a3a; }
            .hit-img { width: 2.75rem; height: 2.75rem; border-radius: 6px; object-fit: cover; flex-shrink: 0; background: #e4e4e7; }
            .hit-placeholder { width: 2.75rem; height: 2.75rem; border-radius: 6px; flex-shrink: 0; background: #e4e4e7; display: flex; align-items: center; justify-content: center; }
            .dark .hit-placeholder { background: #333; }
            .hit-info { min-width: 0; flex: 1; }
            .hit-name { font-size: 0.78rem; font-weight: 600; color: #18181b; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.3; }
            .dark .hit-name { color: #f4f4f5; }
            .hit-price { margin-top: 0.15rem; font-size: 0.78rem; font-weight: 700; color: #4f46e5; }
            .hit-meta { margin-top: 0.1rem; font-size: 0.68rem; color: #71717a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

            /* Highlight */
            .ais-Highlight-highlighted { background: #ddd6fe; color: #4f46e5; border-radius: 2px; font-style: normal; }
            .dark .ais-Highlight-highlighted { background: rgba(99,102,241,0.3); color: #a5b4fc; }

            /* Footer bar */
            #search-dropdown-footer {
                flex-shrink: 0;
                padding: 0.4rem 0.875rem;
                border-top: 1px solid #e4e4e7;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                background: #fafafa;
            }
            .dark #search-dropdown-footer { border-top-color: #333; background: #18181b; }

            /* Empty / welcome state */
            #search-empty-state {
                padding: 1.75rem 1rem;
                text-align: center;
            }

            /* ════════════════════════════════════════════
               CART
            ════════════════════════════════════════════ */
            #amz-cart {
                display: flex; align-items: center; gap: 0.3rem;
                padding: 0.35rem 0.5rem;
                border: 2px solid transparent; border-radius: 2px;
                cursor: pointer; text-decoration: none;
            }
            #amz-cart:hover { border-color: #fff; }
            #amz-cart .amz-cart-label { font-size: 0.8125rem; font-weight: 700; color: #fff; align-self: flex-end; }
            #amz-cart svg { color: #fff; }

            /* ════════════════════════════════════════════
               SECONDARY NAVBAR
            ════════════════════════════════════════════ */
            #amz-navbar {
                background: #232f3e;
                display: flex; align-items: center;
                overflow-x: auto; scrollbar-width: none; -ms-overflow-style: none;
                padding: 0 0.25rem; min-height: 38px;
            }
            #amz-navbar::-webkit-scrollbar { display: none; }
            .amz-nav-item {
                display: flex; align-items: center; gap: 0.3rem;
                padding: 0.3rem 0.625rem; font-size: 0.8125rem; font-weight: 400;
                color: #fff; cursor: pointer; white-space: nowrap;
                border: 2px solid transparent; border-radius: 2px;
                transition: border-color 0.1s; text-decoration: none;
            }
            .amz-nav-item:hover { border-color: #fff; }
            .amz-nav-item.amz-nav-bold { font-weight: 700; }
            #amz-all-btn {
                display: flex; align-items: center; gap: 0.4rem;
                padding: 0.3rem 0.75rem; font-size: 0.8125rem; font-weight: 700;
                color: #fff; cursor: pointer; white-space: nowrap;
                border: 2px solid transparent; border-radius: 2px;
                transition: border-color 0.1s; background: none;
            }
            #amz-all-btn:hover { border-color: #fff; }

            /* ════════════════════════════════════════════
               USER DROPDOWN
            ════════════════════════════════════════════ */
            #amz-user-dropdown { position: relative; }
            #amz-user-menu {
                display: none;
                position: absolute; top: calc(100% + 4px); right: 0;
                min-width: 200px; background: #fff; border: 1px solid #ccc;
                border-radius: 4px; box-shadow: 0 4px 16px rgba(0,0,0,0.2);
                z-index: 9998; padding: 0.5rem 0;
            }
            #amz-user-menu.open { display: block; }
            .amz-menu-item {
                display: flex; align-items: center; gap: 0.5rem;
                padding: 0.5rem 1rem; font-size: 0.875rem; color: #111;
                cursor: pointer; transition: background 0.1s; text-decoration: none;
            }
            .amz-menu-item:hover { background: #f5f5f5; }
            .amz-menu-separator { border: none; border-top: 1px solid #e5e5e5; margin: 0.25rem 0; }

            /* ════════════════════════════════════════════
               MOBILE
            ════════════════════════════════════════════ */
            #amz-mobile-menu-btn {
                display: none; align-items: center; justify-content: center;
                background: none; border: 2px solid transparent; border-radius: 2px;
                padding: 0.35rem 0.6rem; cursor: pointer; color: #fff;
            }
            #amz-mobile-menu-btn:hover { border-color: #fff; }

            @media (max-width: 768px) {
                #amz-mobile-menu-btn { display: flex; }
                #amz-delivery, .amz-desktop-only { display: none !important; }
                #amz-topbar { padding: 0 0.25rem; }
                #search-dropdown { left: -0.5rem; right: -0.5rem; border-radius: 0 0 8px 8px; }
            }

            /* ════════════════════════════════════════════
               MAIN CONTENT
            ════════════════════════════════════════════ */
            #app-main { display: block; width: 100%; min-height: calc(100vh - 98px); }
            [data-flux-sidebar], flux\:sidebar, .flux-sidebar-container { display: none !important; }

            /* ════════════════════════════════════════════
               MOBILE DRAWER
            ════════════════════════════════════════════ */
            #mobile-drawer-overlay {
                display: none; position: fixed; inset: 0;
                background: rgba(0,0,0,0.5); z-index: 9990;
            }
            #mobile-drawer-overlay.open { display: block; }
            #mobile-drawer {
                position: fixed; top: 0; left: 0; bottom: 0; width: 280px;
                background: #131921; z-index: 9991;
                transform: translateX(-100%); transition: transform 0.25s ease;
                display: flex; flex-direction: column; overflow-y: auto;
            }
            #mobile-drawer.open { transform: translateX(0); }
            #mobile-drawer-header {
                display: flex; align-items: center; justify-content: space-between;
                padding: 1rem; background: #232f3e; flex-shrink: 0;
            }
            #mobile-drawer-header span { font-size: 0.9375rem; font-weight: 700; color: #fff; }
            #mobile-drawer-close { background: none; border: none; color: #fff; cursor: pointer; padding: 0.25rem; display: flex; align-items: center; }
            .drawer-item {
                display: flex; align-items: center; gap: 0.75rem;
                padding: 0.75rem 1.25rem; font-size: 0.875rem; color: #fff;
                text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.07);
                transition: background 0.1s; cursor: pointer;
                background: none; border-left: none; border-right: none; border-top: none;
                width: 100%; text-align: left;
            }
            .drawer-item:hover { background: #232f3e; }
            .drawer-section-title {
                padding: 0.875rem 1.25rem 0.375rem; font-size: 0.75rem; font-weight: 700;
                color: #f08804; text-transform: uppercase; letter-spacing: 0.05em;
            }
        </style>
    </head>

    <body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">

        {{-- ══════════════════════════════════════════
             AMAZON-STYLE HEADER
        ═══════════════════════════════════════════════ --}}
        <div id="amz-header">

            <div id="amz-topbar">

                {{-- Mobile menu toggle --}}
                <button id="amz-mobile-menu-btn" onclick="toggleMobileSidebar()" aria-label="Menu">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                {{-- Logo --}}
                <a id="amz-logo" href="{{ route('home') }}" wire:navigate>
                    <svg width="70" height="32" viewBox="0 0 700 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <text x="0" y="220" font-family="Arial Black, Arial" font-weight="900" font-size="220" fill="white">{{ config('app.name') }}</text>
                        <path d="M40 270 Q200 320 360 270" stroke="#f08804" stroke-width="18" fill="none" stroke-linecap="round"/>
                        <polygon points="355,255 380,275 340,280" fill="#f08804"/>
                    </svg>
                </a>

                {{-- Delivery --}}
                <a id="amz-delivery" class="amz-block" href="#">
                    <span class="amz-label">
                        <svg style="display:inline;width:0.75rem;height:0.75rem;margin-right:1px" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 0 1 15 0z"/>
                        </svg>
                        {{ __('Deliver to') }}
                    </span>
                    <span class="amz-value">Maurice</span>
                </a>

                {{-- ════ SEARCH ════ --}}
                <div id="amz-search-wrapper">

                    {{-- Category prepended --}}
                    <select id="amz-search-category">
                        <option>{{ __('All') }}</option>
                        <option>{{ __('Products') }}</option>
                        <option>{{ __('Brands') }}</option>
                        <option>{{ __('Categories') }}</option>
                    </select>

                    {{-- InstantSearch SearchBox mounts here --}}
                    <div id="searchbox" style="flex:1;min-width:0;"></div>

                    {{-- Orange button --}}
                    <button id="amz-search-btn" type="button" aria-label="{{ __('Search') }}">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                        </svg>
                    </button>

                    {{-- ═══ DROPDOWN ═══ --}}
                    <div id="search-dropdown">
                        <div id="search-dropdown-results">
                            {{-- Welcome / empty state --}}
                            <div id="search-empty-state">
                                <svg style="display:block;margin:0 auto 0.625rem;width:2rem;height:2rem;opacity:0.25;color:#71717a" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                                </svg>
                                <p style="font-size:0.8125rem;font-weight:500;color:#71717a">{{ __('Type to search products...') }}</p>
                                <p style="margin-top:0.15rem;font-size:0.72rem;color:#a1a1aa">{{ __('Search by name, SKU, brand or category') }}</p>
                            </div>
                            {{-- Hits --}}
                            <div id="hits" style="display:none;"></div>
                        </div>
                        <div id="search-dropdown-footer">
                            <div id="stats"></div>
                            <span style="font-size:0.7rem;color:#a1a1aa">
                                <kbd style="border:1px solid #d4d4d8;background:#f4f4f5;border-radius:3px;padding:0.1rem 0.3rem;font-family:monospace;font-size:0.65rem">Esc</kbd>
                                {{ __('to close') }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Language --}}
                <a class="amz-block amz-desktop-only" href="#" style="flex-direction:row;gap:0.3rem;align-items:center;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.5">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M3.6 9h16.8M3.6 15h16.8M12 3c-2.4 3-3.6 5.8-3.6 9s1.2 6 3.6 9"/>
                        <path d="M12 3c2.4 3 3.6 5.8 3.6 9s-1.2 6-3.6 9"/>
                    </svg>
                    <span style="font-size:0.8125rem;font-weight:700;color:#fff">FR</span>
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                    </svg>
                </a>

                {{-- Account & Lists --}}
                <div id="amz-user-dropdown" class="amz-desktop-only">
                    <div class="amz-block" onclick="toggleUserMenu()" style="cursor:pointer">
                        <span class="amz-label">{{ __('Hello') }}, {{ auth()->user()?->name ?? __('Sign in') }}</span>
                        <span class="amz-value">
                            {{ __('Account & Lists') }}
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                            </svg>
                        </span>
                    </div>
                    <div id="amz-user-menu">
                        <div style="padding:0.75rem 1rem 0.5rem;border-bottom:1px solid #e5e5e5">
                            <p style="font-size:0.875rem;font-weight:700;color:#111">{{ auth()->user()?->name ?? __('Guest') }}</p>
                            <p style="font-size:0.75rem;color:#555;margin-top:0.1rem">{{ auth()->user()?->email ?? '' }}</p>
                        </div>
                        <a href="#" class="amz-menu-item">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0zM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                            {{ __('Profile') }}
                        </a>
                        <a href="#" class="amz-menu-item">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>
                            {{ __('Settings') }}
                        </a>
                        <hr class="amz-menu-separator">
                        <button class="amz-menu-item" style="width:100%;background:none;border:none;text-align:left;color:#c7202a;font-weight:600"
                            onclick="document.getElementById('logout-form').submit()">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/></svg>
                            {{ __('Logout') }}
                        </button>
                    </div>
                </div>

                {{-- Returns & Orders --}}
                <a class="amz-block amz-desktop-only" href="#">
                    <span class="amz-label">{{ __('Returns') }}</span>
                    <span class="amz-value">& {{ __('Orders') }}</span>
                </a>

                {{-- Cart --}}
                <a id="amz-cart" href="#">
                    <div style="position:relative">
                        <svg width="30" height="30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0z"/>
                        </svg>
                        <span style="position:absolute;top:-4px;right:-6px;font-size:0.8rem;font-weight:700;color:#f08804">0</span>
                    </div>
                    <span class="amz-cart-label">{{ __('Cart') }}</span>
                </a>
            </div>

            {{-- ── Secondary nav bar ── --}}
            <div id="amz-navbar">
                <button id="amz-all-btn" onclick="toggleMobileSidebar()">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                    {{ __('All') }}
                </button>
                <a class="amz-nav-item amz-nav-bold" href="{{ route('home') }}" wire:navigate>{{ __('Home') }}</a>
                <a class="amz-nav-item" href="{{ route('all-products') }}" wire:navigate>{{ __('Products') }}</a>
                <a class="amz-nav-item" href="#">{{ __('Best Sellers') }}</a>
                <a class="amz-nav-item" href="#">{{ __('Flash Sales') }}</a>
                <a class="amz-nav-item" href="#">{{ __('New Arrivals') }}</a>
                <a class="amz-nav-item" href="#">{{ __('Orders') }}</a>
                <a class="amz-nav-item" href="#">{{ __('Customers') }}</a>
                <a class="amz-nav-item" href="#">{{ __('Categories') }}</a>
                <a class="amz-nav-item" href="#">{{ __('Suppliers') }}</a>
                <a class="amz-nav-item" href="#">{{ __('Analytics') }}</a>
                <a class="amz-nav-item" href="#">{{ __('Settings') }}</a>
            </div>
        </div>

        {{-- Hidden logout form --}}
        <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
            @csrf
        </form>

        {{-- ══════════════════════════════════════════
             MOBILE DRAWER
        ═══════════════════════════════════════════════ --}}
        <div id="mobile-drawer-overlay" onclick="closeMobileDrawer()"></div>
        <div id="mobile-drawer" role="dialog" aria-modal="true">
            <div id="mobile-drawer-header">
                <span>
                    <svg style="display:inline;width:1rem;height:1rem;margin-right:0.4rem;vertical-align:middle" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0zM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                    {{ __('Hello') }}, {{ auth()->user()?->name ?? __('Guest') }}
                </span>
                <button id="mobile-drawer-close" onclick="closeMobileDrawer()">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="drawer-section-title">{{ __('Navigate') }}</div>
            <a href="{{ route('home') }}" class="drawer-item" wire:navigate>
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                {{ __('Home') }}
            </a>
            <a href="{{ route('all-products') }}" class="drawer-item" wire:navigate>
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>
                {{ __('Products') }}
            </a>
            <a href="#" class="drawer-item">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0z"/></svg>
                {{ __('Orders') }}
            </a>
            <a href="#" class="drawer-item">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0z"/></svg>
                {{ __('Customers') }}
            </a>
            <div class="drawer-section-title">{{ __('Catalog') }}</div>
            <a href="#" class="drawer-item">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>
                {{ __('Categories') }}
            </a>
            <a href="#" class="drawer-item">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75z"/></svg>
                {{ __('Suppliers') }}
            </a>
            <div class="drawer-section-title">{{ __('Account') }}</div>
            <a href="#" class="drawer-item">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/></svg>
                {{ __('Settings') }}
            </a>
            <button class="drawer-item" style="color:#f87171" onclick="document.getElementById('logout-form').submit()">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/></svg>
                {{ __('Logout') }}
            </button>
        </div>

        {{-- ══════════════════════════════════════════
             MAIN CONTENT
        ═══════════════════════════════════════════════ --}}
        <main id="app-main">
            {{ $slot }}
        </main>

        @fluxScripts
        <x-notification position="top-center" />

        {{-- ══════════════════════════════════════════
             AMAZON-STYLE FOOTER (layout-level)
        ═══════════════════════════════════════════════ --}}
        <style>
            /* ── Footer wrapper ── */
            #amz-footer { font-family: Arial, sans-serif; font-size: 13px; }

            /* ── Content wrapper (reuse amzp-w if homepage loaded, else define) ── */
            .amz-footer-w { max-width: 1500px; margin: 0 auto; padding: 0 10px; }

            /* ── Back to top ── */
            #amz-footer-backtop { background: #37475a; text-align: center; padding: 13px 0; cursor: pointer; }
            #amz-footer-backtop a { color: #fff; font-size: .82rem; text-decoration: none; }
            #amz-footer-backtop a:hover { text-decoration: underline; }

            /* ── Main columns ── */
            #amz-footer-main { background: #232f3e; padding: 32px 0; }
            #amz-footer-main .amz-fcols { display: grid; grid-template-columns: repeat(4, 1fr); gap: 28px; }
            @media (max-width: 768px) { #amz-footer-main .amz-fcols { grid-template-columns: repeat(2, 1fr); gap: 20px; } }
            @media (max-width: 480px) { #amz-footer-main .amz-fcols { grid-template-columns: 1fr; } }
            .amz-fcol h4 { color: #fff; font-size: .84rem; font-weight: 700; margin: 0 0 10px; }
            .amz-fcol a { display: block; color: #ccc; font-size: .74rem; text-decoration: none; margin-bottom: 5px; line-height: 1.5; }
            .amz-fcol a:hover { text-decoration: underline; color: #fff; }

            /* ── Logo row ── */
            #amz-footer-logo { background: #232f3e; border-top: 1px solid #3a4f63; padding: 16px 0; text-align: center; }
            #amz-footer-logo span { color: #febd69; font-weight: 900; font-size: 1.1rem; }
            #amz-footer-logo select { background: #3a4f63; color: #fff; border: 1px solid #5a6f83; border-radius: 3px; padding: 4px 8px; font-size: .75rem; margin-left: 12px; cursor: pointer; }

            /* ── Bottom bar ── */
            #amz-footer-bottom { background: #131921; padding: 14px 0 10px; text-align: center; }
            #amz-footer-bottom .amz-flinks { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 4px; padding: 0 10px; }
            #amz-footer-bottom .amz-flinks a { color: #ccc; font-size: .7rem; text-decoration: none; padding: 4px 8px; border-right: 1px solid #444; }
            #amz-footer-bottom .amz-flinks a:last-child { border-right: none; }
            #amz-footer-bottom .amz-flinks a:hover { text-decoration: underline; }
            #amz-footer-bottom .amz-fcopy { color: #555; font-size: .67rem; margin-top: 8px; }
        </style>

        <footer id="amz-footer">

            {{-- Back to top --}}
            <div id="amz-footer-backtop">
                <a href="#" onclick="window.scrollTo({top:0,behavior:'smooth'});return false">{{ __('Back to top') }}</a>
            </div>

            {{-- Main link columns --}}
            <div id="amz-footer-main">
                <div class="amz-footer-w">
                    <div class="amz-fcols">
                        <div class="amz-fcol">
                            <h4>{{ __('Get to Know Us') }}</h4>
                            <a href="#">{{ __('About') }} {{ config('app.name') }}</a>
                            <a href="#">{{ __('Careers') }}</a>
                            <a href="#">{{ __('Press releases') }}</a>
                            <a href="#">{{ config('app.name') }} {{ __('Science') }}</a>
                        </div>
                        <div class="amz-fcol">
                            <h4>{{ __('Earn Money') }}</h4>
                            <a href="#">{{ __('Sell on') }} {{ config('app.name') }}</a>
                            <a href="#">{{ __('Sell more on') }} {{ config('app.name') }}</a>
                            <a href="#">{{ __('Become affiliate') }}</a>
                            <a href="#">{{ __('Advertise your products') }}</a>
                            <a href="#">{{ __('Self-publish') }}</a>
                            <a href="#">› {{ __('See more') }}</a>
                        </div>
                        <div class="amz-fcol">
                            <h4>{{ __('Payment Methods') }}</h4>
                            <a href="#">{{ config('app.name') }} {{ __('Business card') }}</a>
                            <a href="#">{{ __('Gift cards') }}</a>
                            <a href="#">{{ __('Pay on delivery') }}</a>
                            <a href="#">{{ __('Card payment') }}</a>
                            <a href="#">{{ __('Reload online') }}</a>
                            <a href="#">{{ __('Reload in store') }}</a>
                        </div>
                        <div class="amz-fcol">
                            <h4>{{ __('Need Help?') }}</h4>
                            <a href="#">{{ __('Visit Help Center') }}</a>
                            <a href="#">{{ __('Tools and options') }}</a>
                            <a href="#">{{ __('Shipping rates') }}</a>
                            <a href="#">{{ __('Returns & Refunds') }}</a>
                            <a href="#">{{ __('Recycling') }}</a>
                            <a href="#">{{ __('Manage content') }}</a>
                            <a href="#">{{ __('Accessibility') }}</a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Logo + language row --}}
            <div id="amz-footer-logo">
                <span>{{ config('app.name') }}</span>
                <select aria-label="{{ __('Language') }}">
                    <option>🌐 {{ __('French') }}</option>
                    <option>🌐 {{ __('English') }}</option>
                </select>
            </div>

            {{-- Bottom links --}}
            <div id="amz-footer-bottom">
                <div class="amz-footer-w">
                    <div class="amz-flinks">
                        <a href="#">{{ __('Privacy Policy') }}</a>
                        <a href="#">{{ __('Terms of Use') }}</a>
                        <a href="#">{{ __('Cookies') }}</a>
                        <a href="#">{{ __('Impressum') }}</a>
                        <a href="#">{{ __('Accessibility') }}</a>
                        <a href="#">{{ __('Report an issue') }}</a>
                    </div>
                    <p class="amz-fcopy">© 1996–{{ date('Y') }}, {{ config('app.name') }}.com, Inc. {{ __('or its affiliates') }}</p>
                </div>
            </div>

        </footer>

        {{-- ══════════════════════════════════════════
             INSTANTSEARCH.JS + TYPESENSE
        ═══════════════════════════════════════════════ --}}
        <script>
            // ══════════════════════════════════════════════════════════════════
            //  wire:navigate compatibility
            //  — The layout persists across navigations (header stays in DOM).
            //  — But #searchbox / #hits / #stats / #search-dropdown are ALSO
            //    inside the persistent header, so they are never replaced.
            //  — The real problem: scripts inside <body> at the BOTTOM run once.
            //    After wire:navigate the new page's inline scripts run again,
            //    but this layout script does NOT re-run.
            //  — Solution: wrap everything in initNavbar() and call it:
            //      1. immediately (first load)
            //      2. on livewire:navigated (every SPA navigation)
            //    Guard with a flag so we never mount twice on the same DOM.
            // ══════════════════════════════════════════════════════════════════

            // ── Shared Typesense adapter (created once, reused) ──────────────
            const _tsAdapter = new TypesenseInstantSearchAdapter({
                server: {
                    apiKey: '{{ config('scout.typesense.client-settings.api_key') }}',
                    nodes: [{
                        host:     '{{ parse_url(config('scout.typesense.client-settings.nodes.0.url', 'http://localhost'), PHP_URL_HOST) ?? 'localhost' }}',
                        port:     {{ config('scout.typesense.client-settings.nodes.0.port', 8108) }},
                        protocol: '{{ config('scout.typesense.client-settings.nodes.0.protocol', 'http') }}',
                    }],
                    connectionTimeoutSeconds: 2,
                },
                additionalSearchParameters: {
                    query_by:         'name,sku,brand_name,category_name,short_description',
                    query_by_weights: '10,8,4,3,2',
                    filter_by:        'status:=approved && is_active:=true',
                    sort_by:          'total_sold:desc',
                    highlight_fields: 'name,brand_name,category_name',
                    per_page:         12,
                    num_typos:        1,
                },
            });

            // ── InstantSearch instance (lives across navigations) ────────────
            let _searchInstance = null;

            // ── initNavbar ───────────────────────────────────────────────────
            // Safe to call multiple times: disposes old IS instance first,
            // then remounts widgets into the (possibly new) DOM nodes.
            function initNavbar() {

                // ── 1. Dispose previous IS instance if any ──────────────────
                if (_searchInstance) {
                    try { _searchInstance.dispose(); } catch (_) {}
                    _searchInstance = null;
                }

                // ── 2. Guard: required DOM nodes must exist ──────────────────
                const $searchbox = document.getElementById('searchbox');
                const $hits      = document.getElementById('hits');
                const $stats     = document.getElementById('stats');
                if (!$searchbox || !$hits || !$stats) return;

                // ── 3. Reset hit container & empty state ────────────────────
                $hits.style.display = 'none';
                const $empty = document.getElementById('search-empty-state');
                if ($empty) $empty.style.display = 'block';

                // ── 4. Create fresh IS instance ─────────────────────────────
                _searchInstance = instantsearch({
                    indexName:    '{{ (new \App\Models\Product)->searchableAs() }}',
                    searchClient: _tsAdapter.searchClient,
                    future:       { preserveSharedStateOnUnmount: true },
                });

                _searchInstance.addWidgets([

                    // SearchBox
                    instantsearch.widgets.searchBox({
                        container:   '#searchbox',
                        placeholder: '{{ __('Search products, brands, categories...') }}',
                        autofocus:   false,
                        showSubmit:  false,
                        showReset:   true,
                        queryHook(query, refine) {
                            const empty = document.getElementById('search-empty-state');
                            const hits  = document.getElementById('hits');
                            if (query.trim() === '') {
                                if (empty) empty.style.display = 'block';
                                if (hits)  hits.style.display  = 'none';
                            } else {
                                if (empty) empty.style.display = 'none';
                                if (hits)  hits.style.display  = 'block';
                                openDropdown();
                            }
                            refine(query);
                        },
                    }),

                    // Hits
                    instantsearch.widgets.hits({
                        container: '#hits',
                        templates: {
                            item(hit, { html, components }) {
                                const price    = hit.base_price ? parseFloat(hit.base_price).toFixed(2) : null;
                                const compare  = hit.compare_at_price ? parseFloat(hit.compare_at_price).toFixed(2) : null;
                                const currency = hit.currency || 'USD';
                                const discount = (compare && parseFloat(compare) > parseFloat(price))
                                    ? Math.round((1 - parseFloat(price) / parseFloat(compare)) * 100) : 0;

                                return html`
                                    <a href="#" class="hit-card">
                                        ${hit.image_path
                                            ? html`<img class="hit-img" src="${hit.image_path}" alt="${hit.name}" loading="lazy"/>`
                                            : html`<div class="hit-placeholder">
                                                <svg style="width:1.1rem;height:1.1rem;color:#a1a1aa" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z"/>
                                                </svg>
                                            </div>`
                                        }
                                        <div class="hit-info">
                                            <div class="hit-name">${components.Highlight({ hit, attribute: 'name' })}</div>
                                            ${price ? html`
                                                <div class="hit-price">
                                                    ${price}
                                                    <span style="font-weight:400;font-size:0.67rem;color:#71717a">${currency}</span>
                                                    ${discount > 0 ? html`<span style="font-size:0.65rem;color:#ef4444;margin-left:0.2rem">-${discount}%</span>` : ''}
                                                </div>` : ''}
                                            <div class="hit-meta">
                                                ${hit.category_name || ''}${hit.category_name && hit.brand_name ? ' · ' : ''}${hit.brand_name || ''}
                                            </div>
                                        </div>
                                    </a>`;
                            },
                            empty(results, { html }) {
                                return html`
                                    <div style="padding:1.5rem;text-align:center;color:#71717a">
                                        <p style="font-size:0.875rem;font-weight:500">{{ __('No results for') }} « ${results.query} »</p>
                                        <p style="font-size:0.72rem;margin-top:0.2rem">{{ __('Try a different search term') }}</p>
                                    </div>`;
                            },
                        },
                    }),

                    // Stats
                    instantsearch.widgets.stats({
                        container: '#stats',
                        templates: {
                            text(data, { html }) {
                                if (!data.query) return html``;
                                return html`
                                    <span style="font-size:0.7rem;color:#71717a">
                                        ${data.nbHits.toLocaleString()} {{ __('result') }}${data.nbHits !== 1 ? 's' : ''}
                                        {{ __('in') }} ${data.processingTimeMS}ms
                                        <span style="margin-left:0.3rem;background:#fef3c7;color:#92400e;font-size:0.6rem;font-weight:700;padding:0.1rem 0.3rem;border-radius:9999px">⚡ Typesense</span>
                                    </span>`;
                            },
                        },
                    }),
                ]);

                _searchInstance.start();

                // ── 5. Dropdown logic (re-bound each time) ───────────────────
                const $dropdown = document.getElementById('search-dropdown');
                const $wrapper  = document.getElementById('amz-search-wrapper');
                if (!$dropdown || !$wrapper) return;

                // Remove stale clones and re-attach via a unique marker
                $wrapper._dropdownBound = true;

                function openDropdown()  { $dropdown.classList.add('open'); }
                function closeDropdown() { $dropdown.classList.remove('open'); }

                // Expose globally so queryHook can call openDropdown()
                window.openDropdown  = openDropdown;
                window.closeDropdown = closeDropdown;

                $wrapper.addEventListener('focusin', function handler(e) {
                    if (e.target.tagName === 'INPUT' || e.target.closest('#searchbox')) {
                        openDropdown();
                        const input = $wrapper.querySelector('#searchbox input');
                        if (input && input.value.trim() === '') {
                            const em = document.getElementById('search-empty-state');
                            const ht = document.getElementById('hits');
                            if (em) em.style.display = 'block';
                            if (ht) ht.style.display  = 'none';
                        }
                    }
                });
            } // end initNavbar()

            // ── Global handlers (document-level, registered ONCE) ────────────
            // Use a flag so we don't double-register on re-init calls.
            if (!window._navbarGlobalsBound) {
                window._navbarGlobalsBound = true;

                // Close dropdown / user-menu on outside click
                document.addEventListener('click', function (e) {
                    const $w = document.getElementById('amz-search-wrapper');
                    if ($w && !$w.contains(e.target)) {
                        if (window.closeDropdown) window.closeDropdown();
                    }
                    const $ud = document.getElementById('amz-user-dropdown');
                    if ($ud && !$ud.contains(e.target)) {
                        const m = document.getElementById('amz-user-menu');
                        if (m) m.classList.remove('open');
                    }
                });

                // Escape / Ctrl+K
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        if (window.closeDropdown) window.closeDropdown();
                        closeMobileDrawer();
                        const inp = document.querySelector('#amz-search-wrapper #searchbox input');
                        if (inp) inp.blur();
                    }
                    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                        e.preventDefault();
                        const inp = document.querySelector('#amz-search-wrapper #searchbox input');
                        if (inp) { inp.focus(); if (window.openDropdown) window.openDropdown(); }
                    }
                });

                // ── Re-init InstantSearch after every wire:navigate ──────────
                document.addEventListener('livewire:navigated', function () {
                    // Small tick to ensure Livewire has finished patching the DOM
                    requestAnimationFrame(() => initNavbar());
                });
            }

            // ── User dropdown ─────────────────────────────────────────────────
            function toggleUserMenu() {
                document.getElementById('amz-user-menu').classList.toggle('open');
            }

            // ── Mobile drawer ─────────────────────────────────────────────────
            function toggleMobileSidebar() {
                document.getElementById('mobile-drawer').classList.contains('open')
                    ? closeMobileDrawer() : openMobileDrawer();
            }
            function openMobileDrawer() {
                document.getElementById('mobile-drawer-overlay').classList.add('open');
                document.getElementById('mobile-drawer').classList.add('open');
                document.body.style.overflow = 'hidden';
            }
            function closeMobileDrawer() {
                document.getElementById('mobile-drawer-overlay').classList.remove('open');
                document.getElementById('mobile-drawer').classList.remove('open');
                document.body.style.overflow = '';
            }

            // ── First load ───────────────────────────────────────────────────
            initNavbar();
        </script>
    </body>
</html>
