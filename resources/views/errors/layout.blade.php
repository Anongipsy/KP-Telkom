<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Error') — {{ config('app.name', 'Telkom B2B Contract Monitoring') }}</title>

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

    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 font-sans antialiased flex flex-col items-center justify-center p-4" x-data="themeManager()">
    <!-- Ambient Glow Effect -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[700px] h-[500px] bg-gradient-to-tr from-red-600/10 via-rose-600/5 to-transparent rounded-full blur-3xl"></div>
    </div>

    <!-- Error Card Container -->
    <div class="relative z-10 w-full max-w-lg p-8 sm:p-10 rounded-3xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-xl dark:shadow-2xl backdrop-blur-xl text-center space-y-6">
        <!-- Logo -->
        <div class="flex items-center justify-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-red-600 to-rose-500 flex items-center justify-center shadow-lg shadow-red-600/30">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <span class="text-sm font-bold text-slate-900 dark:text-white tracking-wider uppercase">Telkom B2B Monitoring</span>
        </div>

        <!-- Error Icon / Code -->
        <div class="space-y-2">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl @yield('icon-bg', 'bg-red-500/10 border border-red-500/20 text-red-500')">
                @yield('icon')
            </div>
            <h1 class="text-4xl font-extrabold font-mono text-slate-900 dark:text-white tracking-tight">@yield('code', 'Error')</h1>
            <h2 class="text-lg font-bold text-slate-800 dark:text-slate-200">@yield('heading', 'Terjadi Kesalahan')</h2>
        </div>

        <!-- Message Description (Safe, no stack traces) -->
        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
            @yield('message', 'Maaf, terjadi kesalahan saat memproses permintaan Anda. Tim kami telah mencatat peristiwa ini.')
        </p>

        <!-- Actions -->
        <div class="flex items-center justify-center gap-3 pt-2">
            <a
                href="{{ url('/') }}"
                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-white font-bold text-xs shadow-lg shadow-red-600/25 transition-all"
            >
                Kembali ke Dashboard
            </a>
            <button
                type="button"
                onclick="window.history.back()"
                class="px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white text-xs font-semibold border border-slate-200 dark:border-slate-700 transition-colors cursor-pointer"
            >
                Kembali
            </button>
        </div>
    </div>

    <p class="relative z-10 mt-6 text-[11px] text-slate-500 dark:text-slate-600 font-mono">
        Telkom Indonesia &bull; Enterprise Contract Monitoring
    </p>
</body>
</html>
