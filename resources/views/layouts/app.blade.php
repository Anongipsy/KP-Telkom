<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Telkom B2B Contract Monitoring') }} - @yield('title', 'Dashboard')</title>

    <!-- Anti-flash theme script -->
    <script>
        (function() {
            const theme = localStorage.getItem('theme');
            if (theme === 'pink') {
                document.documentElement.classList.add('pink');
                document.documentElement.classList.remove('dark');
            } else if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
                document.documentElement.classList.remove('pink');
            } else {
                document.documentElement.classList.remove('dark', 'pink');
            }
        })();
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full font-sans antialiased text-slate-900 bg-slate-50 dark:text-slate-100 dark:bg-slate-950 selection:bg-red-500 selection:text-white transition-colors duration-200" x-data="{ mobileSidebarOpen: false, ...themeManager() }">
    <div class="min-h-screen flex">
        <!-- ========================================================================= -->
        <!-- LEFT SIDEBAR NAVIGATION (Desktop: Fixed w-64, Mobile: Off-canvas Drawer) -->
        <!-- ========================================================================= -->

        <!-- Mobile Backdrop -->
        <div
            x-show="mobileSidebarOpen"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="mobileSidebarOpen = false"
            class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-40 md:hidden"
            style="display: none;"
        ></div>

        <!-- Sidebar Container -->
        <aside
            :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800/80 flex flex-col justify-between transition-transform duration-300 ease-in-out shadow-lg dark:shadow-none"
        >
            <!-- Top Part: Brand & Navigation -->
            <div class="flex-1 flex flex-col overflow-y-auto custom-scrollbar">
                <!-- Brand Header -->
                <div class="h-16 px-5 flex items-center justify-between border-b border-slate-200 dark:border-slate-800/80 bg-slate-50/80 dark:bg-slate-950/40">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-white/5 border border-slate-200 dark:border-white/10 p-1.5 flex items-center justify-center shadow-sm dark:shadow-lg dark:shadow-red-600/10 transition-transform group-hover:scale-105">
                            <img src="{{ asset('images/telkom-logo.png') }}" alt="Telkom Indonesia Logo" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <span class="text-sm font-extrabold text-slate-900 dark:text-white tracking-tight block">Telkom B2B</span>
                            <span class="text-[10px] text-slate-500 dark:text-slate-400 block -mt-0.5 uppercase tracking-wider font-mono">Contract Monitor</span>
                        </div>
                    </a>

                    <!-- Mobile Close Button -->
                    <button
                        type="button"
                        @click="mobileSidebarOpen = false"
                        class="md:hidden p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Primary Action Button -->
                <div class="p-4 border-b border-slate-200 dark:border-slate-800/50">
                    <a
                        href="{{ route('contracts.create') }}"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-white font-bold text-xs shadow-md shadow-red-600/20 transition-all group"
                    >
                        <svg class="w-4 h-4 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>+ Tambah Kontrak</span>
                    </a>
                </div>

                <!-- Navigation Links List -->
                <nav class="p-3 space-y-1.5">
                    @php
                        $unreadCountNav = \App\Models\Notification::where('user_id', auth()->id())->unread()->count();
                    @endphp

                    <!-- Menu 1: Dashboard Overview -->
                    <a
                        href="{{ route('dashboard') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('dashboard') ? 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/25 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800/60 border border-transparent' }}"
                    >
                        <svg class="w-4 h-4 {{ request()->routeIs('dashboard') ? 'text-red-600 dark:text-red-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                        <span class="flex-1">Dashboard (Overview)</span>
                    </a>

                    <!-- Menu 2: Contracts & Pipeline Management -->
                    <a
                        href="{{ route('contracts.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('contracts.*') && !request()->routeIs('contracts.create') ? 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/25 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800/60 border border-transparent' }}"
                    >
                        <svg class="w-4 h-4 {{ request()->routeIs('contracts.*') && !request()->routeIs('contracts.create') ? 'text-red-600 dark:text-red-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="flex-1">Kontrak & Pipeline</span>
                    </a>

                    <!-- Menu 3: Early Warning & Notifications -->
                    <a
                        href="{{ route('monitoring.index') }}"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('monitoring.*') ? 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/25 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800/60 border border-transparent' }}"
                    >
                        <svg class="w-4 h-4 {{ request()->routeIs('monitoring.*') ? 'text-red-600 dark:text-red-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="flex-1">Early Warning & Alerts</span>
                        @if($unreadCountNav > 0)
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-600 text-white shadow-sm">
                                {{ $unreadCountNav > 9 ? '9+' : $unreadCountNav }}
                            </span>
                        @endif
                    </a>

                    <!-- Admin Section (Only for Admin Role) -->
                    @if (auth()->check() && auth()->user()->isAdmin())
                        <div class="pt-4 pb-1">
                            <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 font-mono">
                                Administrator
                            </p>
                        </div>
                        <a
                            href="{{ route('admin.audit-logs') }}"
                            class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all {{ request()->routeIs('admin.audit-logs') ? 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/25 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-300 dark:hover:text-white dark:hover:bg-slate-800/60 border border-transparent' }}"
                        >
                            <svg class="w-4 h-4 {{ request()->routeIs('admin.audit-logs') ? 'text-red-600 dark:text-red-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                            <span class="flex-1">Audit Trail & Log</span>
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 font-mono">ADMIN</span>
                        </a>
                    @endif
                </nav>
            </div>

            <!-- Bottom Part: User Profile & Logout -->
            <div class="p-3 border-t border-slate-200 dark:border-slate-800/80 bg-slate-50/80 dark:bg-slate-950/60">
                <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3 shadow-sm dark:shadow-none">
                    <div class="flex items-center gap-2.5 overflow-hidden">
                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-xs font-bold text-slate-700 dark:text-slate-200 shrink-0">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                            <span class="inline-block text-[10px] font-semibold text-slate-500 dark:text-slate-400 truncate">
                                {{ auth()->user()->isAdmin() ? 'Admin / Developer' : 'Account Manager' }}
                            </span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                            title="Keluar (Logout)"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- ========================================================================= -->
        <!-- MAIN CONTENT WRAPPER (Offset ml-64 on desktop) -->
        <!-- ========================================================================= -->
        <div class="flex-1 flex flex-col md:ml-64 min-w-0">
            <!-- Topbar Header -->
            <header class="sticky top-0 z-30 h-16 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-800/80 px-4 sm:px-6 lg:px-8 flex items-center justify-between shadow-sm dark:shadow-none">
                <!-- Left: Mobile Toggle & Breadcrumbs -->
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="mobileSidebarOpen = !mobileSidebarOpen"
                        class="md:hidden p-2 rounded-lg text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-red-500"
                        aria-label="Toggle navigation"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>

                    <div>
                        <h1 class="text-sm sm:text-base font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                            @yield('page_title', 'Dashboard')
                        </h1>
                    </div>
                </div>

                <!-- Right: Quick Actions (3-Theme Switcher, Refresh, Alerts) -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- 3-Theme Switcher Dropdown -->
                    <div class="relative" x-data="{ themeMenuOpen: false }" @click.outside="themeMenuOpen = false">
                        <button
                            type="button"
                            @click="themeMenuOpen = !themeMenuOpen"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800/80 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-all shadow-sm cursor-pointer text-xs font-semibold"
                            title="Pilih Tema Tampilan"
                        >
                            <!-- Theme Icon -->
                            <template x-if="theme === 'light'">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </template>
                            <template x-if="theme === 'dark'">
                                <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                                </svg>
                            </template>
                            <template x-if="theme === 'pink'">
                                <span class="text-xs">🌸</span>
                            </template>

                            <span class="hidden sm:inline" x-text="theme === 'light' ? 'Terang' : (theme === 'dark' ? 'Gelap' : 'Soft Pink')"></span>

                            <svg class="w-3 h-3 text-slate-400 transition-transform" :class="{ 'rotate-180': themeMenuOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Theme Dropdown Popover -->
                        <div
                            x-show="themeMenuOpen"
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                            x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                            class="absolute right-0 mt-2 w-44 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl py-1.5 z-50 overflow-hidden text-xs"
                            style="display: none;"
                        >
                            <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800">
                                Pilihan Tema
                            </div>

                            <button
                                type="button"
                                @click="setTheme('light'); themeMenuOpen = false"
                                class="w-full flex items-center justify-between px-3 py-2 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors cursor-pointer"
                                :class="{ 'font-bold text-red-600 dark:text-red-400 bg-red-50/50 dark:bg-slate-800/80': theme === 'light' }"
                            >
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                    <span>Mode Terang</span>
                                </div>
                                <span x-show="theme === 'light'" class="text-red-500 font-bold">✓</span>
                            </button>

                            <button
                                type="button"
                                @click="setTheme('dark'); themeMenuOpen = false"
                                class="w-full flex items-center justify-between px-3 py-2 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors cursor-pointer"
                                :class="{ 'font-bold text-red-600 dark:text-red-400 bg-red-50/50 dark:bg-slate-800/80': theme === 'dark' }"
                            >
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                                    </svg>
                                    <span>Mode Gelap</span>
                                </div>
                                <span x-show="theme === 'dark'" class="text-red-500 font-bold">✓</span>
                            </button>

                            <button
                                type="button"
                                @click="setTheme('pink'); themeMenuOpen = false"
                                class="w-full flex items-center justify-between px-3 py-2 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors cursor-pointer"
                                :class="{ 'font-bold text-rose-600 dark:text-rose-400 bg-rose-50/50 dark:bg-slate-800/80': theme === 'pink' }"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="text-sm">🌸</span>
                                    <span>Soft Pink</span>
                                </div>
                                <span x-show="theme === 'pink'" class="text-rose-500 font-bold">✓</span>
                            </button>
                        </div>
                    </div>

                    <!-- Quick Refresh Data from Google Sheets -->
                    <form action="{{ route('dashboard.refresh') }}" method="POST">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800/80 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700/80 text-xs font-bold text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-all shadow-sm cursor-pointer"
                            title="Sinkronisasi & Segarkan Data langsung dari Google Sheets"
                        >
                            <svg class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span class="hidden sm:inline">Refresh Sheets</span>
                        </button>
                    </form>

                    <!-- Early Warning Bell Link -->
                    <a
                        href="{{ route('monitoring.index') }}?tab=notifications"
                        class="relative p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800/80 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700/80 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-colors"
                        title="Pusat Peringatan & Notifikasi ({{ $unreadCountNav }} Belum Dibaca)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        @if($unreadCountNav > 0)
                            <span class="absolute -top-1 -right-1 flex h-4 w-4">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-red-600 text-[9px] font-bold text-white items-center justify-center">
                                    {{ $unreadCountNav > 9 ? '9+' : $unreadCountNav }}
                                </span>
                            </span>
                        @endif
                    </a>
                </div>
            </header>

            <!-- Flash Alerts Banner -->
            <div class="px-4 sm:px-6 lg:px-8 pt-4">
                @if (session('status'))
                    <div class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/25 text-emerald-700 dark:text-emerald-300 text-xs flex items-center justify-between gap-3 shadow-md">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>{{ session('status') }}</span>
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="p-3.5 rounded-xl bg-red-500/10 border border-red-500/25 text-red-700 dark:text-red-300 text-xs flex items-center justify-between gap-3 shadow-md">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>{{ session('error') }}</span>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Page Main Content -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="border-t border-slate-200 dark:border-slate-800/80 bg-white/60 dark:bg-slate-900/40 py-4 text-center text-xs text-slate-500">
                <p>&copy; {{ date('Y') }} Telkom Indonesia &bull; Web Dashboard & Monitoring Kontrak Kerja B2B</p>
            </footer>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
