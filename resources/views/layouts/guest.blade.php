<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Telkom B2B Contract Monitoring') }} - @yield('title', 'Login')</title>

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-900 bg-slate-50 dark:text-slate-100 dark:bg-slate-950 selection:bg-red-500 selection:text-white transition-colors duration-200" x-data="themeManager()">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative overflow-hidden">
        <!-- 3-Theme Switcher on Guest Header -->
        <div class="absolute top-4 right-4 z-20" x-data="{ open: false }" @click.outside="open = false">
            <button
                type="button"
                @click="open = !open"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white/80 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:text-red-600 dark:hover:text-white shadow-sm transition-all cursor-pointer text-xs font-semibold backdrop-blur-md"
                title="Pilih Tema Tampilan"
            >
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

                <span class="hidden sm:inline" x-text="theme === 'light' ? 'Mode Terang' : (theme === 'dark' ? 'Mode Gelap' : 'Soft Pink')"></span>

                <svg class="w-3 h-3 text-slate-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <!-- Dropdown Menu -->
            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                class="absolute right-0 mt-2 w-44 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl py-1.5 z-50 overflow-hidden text-xs"
                style="display: none;"
            >
                <button
                    type="button"
                    @click="setTheme('light'); open = false"
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
                    @click="setTheme('dark'); open = false"
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
                    @click="setTheme('pink'); open = false"
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

        <!-- Ambient background gradients -->
        <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-40 -right-40 w-96 h-96 bg-red-600/10 dark:bg-red-600/15 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-rose-600/10 dark:bg-red-800/10 rounded-full blur-3xl"></div>
        </div>

        <div class="relative z-10 sm:mx-auto sm:w-full sm:max-w-md">
            @yield('content')
        </div>
    </div>
</body>
</html>
