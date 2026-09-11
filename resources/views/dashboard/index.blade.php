@extends('layouts.app')

@section('title', 'Dashboard Overview')
@section('page_title')
    <span class="text-slate-900 dark:text-white">Dashboard Overview</span>
    <span class="text-xs text-slate-400 dark:text-slate-500 font-mono hidden sm:inline">&bull; </span>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-6 rounded-3xl bg-white dark:bg-gradient-to-r dark:from-slate-900 dark:via-slate-900/90 dark:to-slate-950 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-red-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 space-y-1">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold font-mono bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20">
                    PORTAL MONITORING
                </span>
                <span class="text-xs text-slate-500 dark:text-slate-400">&bull; {{ date('l, d F Y') }}</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                Selamat Datang, {{ $user->name }}
            </h2>
        </div>

        <div class="relative z-10 flex flex-wrap items-center gap-2.5">
            <a
                href="{{ route('contracts.index') }}"
                class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-700 text-xs font-bold transition-all flex items-center gap-2"
            >
                <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Buka Kontrak & Pipeline</span>
            </a>
            <a
                href="{{ route('monitoring.index') }}"
                class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-md shadow-red-600/20 transition-all flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Pusat Early Warning</span>
            </a>
        </div>
    </div>

    <!-- Error Banner if Google Sheets failed -->
    @if ($errorMessage)
        <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-700 dark:text-amber-300 text-xs flex items-center justify-between gap-3 shadow-sm">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 shrink-0 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <span>{{ $errorMessage }}</span>
            </div>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- FILTER BAR: PERIODE TAHUN (ALL TIME / SPESIFIK) & NAMA GC -->
    <!-- ========================================================================= -->
    <div class="p-5 rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl space-y-3">
        <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <span class="p-2 rounded-xl bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                    </span>
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Filter Data:</span>
                </div>

                <!-- Filter Tahun (All Time / Specific) -->
                <div class="w-48">
                    <label for="filter-tahun" class="sr-only">Tahun</label>
                    <div class="relative">
                        <select
                            id="filter-tahun"
                            name="tahun"
                            onchange="this.form.submit()"
                            class="w-full pl-3 pr-8 py-2 rounded-xl text-xs font-semibold bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all appearance-none cursor-pointer"
                        >
                            <option value="" {{ empty($selectedYear) || strtolower($selectedYear) === 'all' ? 'selected' : '' }}>
                                📅 Semua Tahun (All Time)
                            </option>
                            @foreach($availableYears as $yearOption)
                                <option value="{{ $yearOption }}" {{ $selectedYear == $yearOption ? 'selected' : '' }}>
                                    Tahun {{ $yearOption }}
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Filter Nama GC -->
                <div class="w-56">
                    <label for="filter-gc" class="sr-only">Nama GC</label>
                    <div class="relative">
                        <select
                            id="filter-gc"
                            name="nama_gc"
                            onchange="this.form.submit()"
                            class="w-full pl-3 pr-8 py-2 rounded-xl text-xs font-semibold bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all appearance-none cursor-pointer"
                        >
                            <option value="" {{ empty($selectedGc) || strtolower($selectedGc) === 'all' ? 'selected' : '' }}>
                                🏢 Semua Nama GC
                            </option>
                            @foreach($availableGCs as $gcOption)
                                <option value="{{ $gcOption }}" {{ $selectedGc == $gcOption ? 'selected' : '' }}>
                                    {{ $gcOption }}
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <button
                    type="submit"
                    class="px-3.5 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-white text-xs font-bold transition-all shadow-sm flex items-center gap-1.5"
                >
                    <span>Terapkan</span>
                </button>

                @if(!empty($selectedYear) || !empty($selectedGc))
                    <a
                        href="{{ route('dashboard') }}"
                        class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold transition-all flex items-center gap-1"
                        title="Reset Filter"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span>Reset</span>
                    </a>
                @endif
            </div>

            <!-- Active Filter Context Badges -->
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="text-slate-500 dark:text-slate-400">
                    Data: <strong class="font-mono text-slate-900 dark:text-white">{{ count($contracts) }}</strong> / <strong class="font-mono text-slate-600 dark:text-slate-400">{{ $totalUnfilteredCount ?? count($contracts) }}</strong> LOP
                </span>
                @if(!empty($selectedYear))
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                        <span>Tahun: {{ $selectedYear }}</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                        <span>All Time</span>
                    </span>
                @endif
                @if(!empty($selectedGc))
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-sky-500/10 text-sky-600 dark:text-sky-400 border border-sky-500/20 truncate max-w-[150px]">
                        <span>GC: {{ $selectedGc }}</span>
                    </span>
                @endif
            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- SECTION 1: EXECUTIVE KPI SUMMARY CARDS (PRD FR-03) -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- KPI 1: Total Pipeline Revenue (From Nilai Realisasi Win) -->
        <div class="p-5 rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl relative overflow-hidden group hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Total Nilai Pipeline</span>
                    <div class="w-9 h-9 rounded-xl bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-600 dark:text-red-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <p class="text-xl font-extrabold text-slate-900 dark:text-white font-mono tracking-tight">{{ $kpi['total_pipeline_revenue_formatted'] ?? 'Rp 0' }}</p>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">
                        Nilai Realisasi Win &bull; {{ $kpi['total_lop'] ?? 0 }} kontrak {{ !empty($selectedYear) ? 'tahun ' . $selectedYear : '' }}
                    </p>
                </div>
            </div>

            <!-- Mini-badges: Kontrak Berjalan (Hijau) & Kontrak Selesai (Merah) -->
            <div class="mt-3.5 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center gap-2">
                <a href="{{ route('contracts.index', array_filter(['status_kontrak' => 'BERJALAN', 'tahun' => $selectedYear ?? null, 'nama_gc' => $selectedGc ?? null])) }}" 
                   title="Filter Kontrak Berjalan"
                   class="flex-1 flex items-center justify-between px-2.5 py-1.5 rounded-xl bg-emerald-50/70 hover:bg-emerald-100/80 dark:bg-emerald-950/30 dark:hover:bg-emerald-900/50 border border-slate-200 dark:border-slate-800 hover:border-emerald-300 dark:hover:border-emerald-700 transition-all group/badge shadow-xs">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 shrink-0" style="width: 8px; height: 8px;"></span>
                        <span class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 truncate">Berjalan</span>
                    </div>
                    <span class="font-mono text-xs font-extrabold text-emerald-800 dark:text-emerald-200 ml-1.5 group-hover/badge:scale-105 transition-transform">{{ $kpi['kontrak_berjalan_count'] ?? 0 }}</span>
                </a>

                <a href="{{ route('contracts.index', array_filter(['status_kontrak' => 'SELESAI', 'tahun' => $selectedYear ?? null, 'nama_gc' => $selectedGc ?? null])) }}" 
                   title="Filter Kontrak Selesai"
                   class="flex-1 flex items-center justify-between px-2.5 py-1.5 rounded-xl bg-rose-50/70 hover:bg-rose-100/80 dark:bg-rose-950/30 dark:hover:bg-rose-900/50 border border-slate-200 dark:border-slate-800 hover:border-rose-300 dark:hover:border-rose-700 transition-all group/badge shadow-xs">
                    <div class="flex items-center gap-2 min-w-0">
                        <span class="inline-block w-2 h-2 rounded-full bg-rose-500 shrink-0" style="width: 8px; height: 8px;"></span>
                        <span class="text-[11px] font-semibold text-rose-700 dark:text-rose-300 truncate">Selesai</span>
                    </div>
                    <span class="font-mono text-xs font-extrabold text-rose-800 dark:text-rose-200 ml-1.5 group-hover/badge:scale-105 transition-transform">{{ $kpi['kontrak_selesai_count'] ?? 0 }}</span>
                </a>
            </div>
        </div>

        <!-- KPI 2: Realized Revenue & Billcomp -->
        <div class="p-5 rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl relative overflow-hidden group hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Realisasi Billcomp</span>
                    <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-3">
                    <div class="flex items-baseline justify-between">
                        <p class="text-xl font-extrabold text-slate-900 dark:text-white font-mono tracking-tight">{{ $kpi['total_realized_revenue_formatted'] ?? 'Rp 0' }}</p>
                        <span class="text-xs font-extrabold text-emerald-600 dark:text-emerald-400 font-mono">{{ $kpi['realized_percentage'] ?? 0 }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full mt-2.5 overflow-hidden">
                        <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full rounded-full transition-all duration-500" style="width: {{ min(100, $kpi['realized_percentage'] ?? 0) }}%"></div>
                    </div>
                    @if(!empty($kpi['total_nilai_bc']) && $kpi['total_nilai_bc'] > 0)
                        <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-2">
                            Total Nilai BC: <span class="font-mono font-bold text-slate-700 dark:text-slate-300">{{ $kpi['total_nilai_bc_formatted'] }}</span>
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <!-- KPI 3: Expiring Soon Contracts (0 - 60 Days) -->
        <div class="p-5 rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl relative overflow-hidden group hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Mendekati Jatuh Tempo</span>
                    <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <div>
                        <p class="text-2xl font-extrabold text-amber-600 dark:text-amber-400 font-mono">{{ $kpi['expiring_soon_contracts'] ?? 0 }}</p>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">0 – 60 hari tersisa</p>
                    </div>
                    <a href="{{ route('monitoring.index') }}" class="text-[11px] font-bold text-amber-600 dark:text-amber-400 hover:underline">
                        Lihat &rarr;
                    </a>
                </div>
            </div>
        </div>

        <!-- KPI 4: Overdue Contracts (< 0 Days) -->
        <div class="p-5 rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl relative overflow-hidden group hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Lewat Jatuh Tempo</span>
                    <div class="w-9 h-9 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-600 dark:text-rose-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <div>
                        <p class="text-2xl font-extrabold text-rose-600 dark:text-rose-500 font-mono">{{ $kpi['overdue_contracts'] ?? 0 }}</p>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">&lt; 0 hari (Perlu Perpanjangan)</p>
                    </div>
                    <a href="{{ route('monitoring.index') }}" class="text-[11px] font-bold text-rose-600 dark:text-rose-400 hover:underline">
                        Lihat &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- SECTION 2: PIPELINE STAGE DISTRIBUTION SUMMARY (F0–F4) -->
    <!-- ========================================================================= -->
    <div class="p-6 rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span>Distribusi Tahapan Pipeline (F0–F4)</span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Ringkasan kemajuan kontrak {{ !empty($selectedYear) ? 'tahun ' . $selectedYear : 'seluruh periode' }} {{ !empty($selectedGc) ? '&bull; ' . $selectedGc : '' }}
                </p>
            </div>

            <a
                href="{{ route('contracts.index') }}?view=kanban"
                class="text-xs font-bold text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 flex items-center gap-1 transition-colors"
            >
                <span>Buka Papan Kanban Lengkap</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                </svg>
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            @foreach ($stages as $stageKey => $stageData)
                @php
                    $stageColor = match($stageKey) {
                        'F0' => 'from-slate-50 to-white dark:from-slate-800 dark:to-slate-900 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300',
                        'F1' => 'from-sky-50 to-white dark:from-sky-950/40 dark:to-slate-900 border-sky-200 dark:border-sky-800/40 text-sky-700 dark:text-sky-400',
                        'F2' => 'from-indigo-50 to-white dark:from-indigo-950/40 dark:to-slate-900 border-indigo-200 dark:border-indigo-800/40 text-indigo-700 dark:text-indigo-400',
                        'F3' => 'from-amber-50 to-white dark:from-amber-950/40 dark:to-slate-900 border-amber-200 dark:border-amber-800/40 text-amber-700 dark:text-amber-400',
                        'F4' => 'from-emerald-50 to-white dark:from-emerald-950/40 dark:to-slate-900 border-emerald-200 dark:border-emerald-800/40 text-emerald-700 dark:text-emerald-400',
                        default => 'from-slate-50 to-white dark:from-slate-900 dark:to-slate-950 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-400',
                    };
                @endphp
                <div class="p-4 rounded-2xl bg-gradient-to-b {{ $stageColor }} border shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-0.5 rounded font-mono text-[10px] font-extrabold bg-white dark:bg-slate-950/60 border border-slate-200 dark:border-white/10 text-slate-800 dark:text-white">
                                {{ $stageKey }}
                            </span>
                            <span class="text-xs font-bold font-mono">{{ $stageData['count'] }} LOP</span>
                        </div>
                        <p class="text-xs font-bold text-slate-900 dark:text-white mt-2 truncate">{{ $stageData['label'] }}</p>
                    </div>
                    <div class="mt-3 pt-2 border-t border-slate-200 dark:border-white/5">
                        <p class="text-[10px] text-slate-500 dark:text-slate-400">Total Nilai Win:</p>
                        <p class="text-xs font-extrabold text-slate-900 dark:text-white font-mono truncate">{{ $stageData['total_revenue_formatted'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- SECTION 3: VISUAL ANALYTICS & CHARTS -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Chart 1: Donut Chart - Distribusi Tahapan Pipeline (Stage F0–F4) -->
        <div class="lg:col-span-5 p-6 rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl flex flex-col justify-between space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                            </svg>
                        </span>
                        <span>Distribusi Tahapan Pipeline</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Proporsi kontrak berdasarkan tahapan pipeline F0–F4
                    </p>
                </div>
                <span class="px-2.5 py-1 rounded-xl text-[11px] font-mono font-extrabold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                    {{ count($contracts) }} LOP
                </span>
            </div>

            <!-- Canvas Container with Center Label -->
            <div class="relative w-full h-64 sm:h-72 flex items-center justify-center">
                <canvas id="stageDoughnutChart" class="max-h-full max-w-full"></canvas>
                <!-- Center overlay text for donut -->
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none select-none">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 dark:text-slate-500">Total LOP</span>
                    <span class="text-2xl sm:text-3xl font-extrabold font-mono text-slate-900 dark:text-white">{{ count($contracts) }}</span>
                    <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium">Kontrak</span>
                </div>
            </div>

            <!-- Mini Legend Badges -->
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80 grid grid-cols-5 gap-1.5 text-center">
                <div class="p-1.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/60 dark:border-slate-800">
                    <div class="flex items-center justify-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                        <span class="font-mono text-[10px] font-bold text-slate-700 dark:text-slate-300">F0</span>
                    </div>
                    <p class="font-mono text-xs font-extrabold text-slate-900 dark:text-white mt-0.5">{{ $stageChartData['counts'][0] ?? 0 }}</p>
                </div>
                <div class="p-1.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/60 dark:border-slate-800">
                    <div class="flex items-center justify-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-sky-500"></span>
                        <span class="font-mono text-[10px] font-bold text-sky-600 dark:text-sky-400">F1</span>
                    </div>
                    <p class="font-mono text-xs font-extrabold text-slate-900 dark:text-white mt-0.5">{{ $stageChartData['counts'][1] ?? 0 }}</p>
                </div>
                <div class="p-1.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/60 dark:border-slate-800">
                    <div class="flex items-center justify-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        <span class="font-mono text-[10px] font-bold text-indigo-600 dark:text-indigo-400">F2</span>
                    </div>
                    <p class="font-mono text-xs font-extrabold text-slate-900 dark:text-white mt-0.5">{{ $stageChartData['counts'][2] ?? 0 }}</p>
                </div>
                <div class="p-1.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/60 dark:border-slate-800">
                    <div class="flex items-center justify-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span class="font-mono text-[10px] font-bold text-amber-600 dark:text-amber-400">F3</span>
                    </div>
                    <p class="font-mono text-xs font-extrabold text-slate-900 dark:text-white mt-0.5">{{ $stageChartData['counts'][3] ?? 0 }}</p>
                </div>
                <div class="p-1.5 rounded-xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/60 dark:border-slate-800">
                    <div class="flex items-center justify-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="font-mono text-[10px] font-bold text-emerald-600 dark:text-emerald-400">F4</span>
                    </div>
                    <p class="font-mono text-xs font-extrabold text-slate-900 dark:text-white mt-0.5">{{ $stageChartData['counts'][4] ?? 0 }}</p>
                </div>
            </div>
        </div>

        <!-- Chart 2: Horizontal Bar Chart - Revenue per Group Company (Nama GC) -->
        <div class="lg:col-span-7 p-6 rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl flex flex-col justify-between space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </span>
                        <span>Revenue per Group Company (Nama GC)</span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Perbandingan total nilai realisasi win per korporasi / satker
                    </p>
                </div>
                <span class="px-2.5 py-1 rounded-xl text-[11px] font-mono font-extrabold bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20">
                    {{ $kpi['total_pipeline_revenue_formatted'] ?? 'Rp 0' }}
                </span>
            </div>

            <!-- Canvas Container -->
            <div class="relative w-full h-64 sm:h-72 flex items-center justify-center">
                @if(count($gcChartData['labels']) > 0)
                    <canvas id="gcBarChart" class="w-full h-full"></canvas>
                @else
                    <div class="text-center py-12 text-slate-400 dark:text-slate-500">
                        <svg class="w-8 h-8 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <p class="text-xs">Tidak ada data revenue GC untuk filter ini.</p>
                    </div>
                @endif
            </div>

            <!-- Footer summary note -->
            <div class="pt-3 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    <span>Diurutkan berdasarkan nilai revenue tertinggi</span>
                </span>
                <span class="font-mono font-bold text-slate-700 dark:text-slate-300">
                    {{ count($gcChartData['labels']) }} Group Company
                </span>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- SECTION 4: URGENT EXPIRATION ALERTS & RECENT CONTRACTS -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Left: Urgent Expiration Box (5 Cols) -->
        <div class="lg:col-span-5 p-6 rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <span>Perlu Perhatian Segera</span>
                </h3>
                <a href="{{ route('monitoring.index') }}" class="text-[11px] font-bold text-red-600 dark:text-red-400 hover:underline">
                    Kelola Semua
                </a>
            </div>

            @php
                $urgentList = array_slice(array_merge($overdueContracts, $expiringSoonContracts), 0, 4);
            @endphp

            @if(count($urgentList) > 0)
                <div class="space-y-2.5">
                    @foreach($urgentList as $urgent)
                        @php
                            $isOverdue = ($urgent['expiration_status'] ?? '') === 'OVERDUE';
                        @endphp
                        <a
                            href="{{ route('contracts.show', $urgent['lop']) }}"
                            class="block p-3 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border {{ $isOverdue ? 'border-rose-300 dark:border-rose-500/30 hover:border-rose-500' : 'border-amber-300 dark:border-amber-500/30 hover:border-amber-500' }} transition-all group shadow-sm"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="space-y-0.5 overflow-hidden">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-[11px] font-bold text-red-600 dark:text-red-400 group-hover:underline">{{ $urgent['lop'] }}</span>
                                        @if(!empty($urgent['id_mytens']))
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-mono text-slate-500 dark:text-slate-400 bg-slate-200/60 dark:bg-slate-800">
                                                {{ $urgent['id_mytens'] }}
                                            </span>
                                        @endif
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-extrabold font-mono {{ $isOverdue ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' }}">
                                            {{ $isOverdue ? 'OVERDUE (' . abs($urgent['days_remaining']) . 'h lalu)' : $urgent['days_remaining'] . ' hari lagi' }}
                                        </span>
                                    </div>
                                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">
                                        {{ $urgent['satker'] ?: '-' }}
                                    </p>
                                    @if(!empty($urgent['nama_gc']))
                                        <p class="text-[10px] text-slate-500 truncate">
                                            🏢 {{ $urgent['nama_gc'] }}
                                        </p>
                                    @endif
                                </div>
                                <span class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300 shrink-0">{{ $urgent['revenue_formatted'] ?? 'Rp 0' }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="py-8 text-center text-slate-500 space-y-1">
                    <svg class="w-8 h-8 mx-auto text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Seluruh Kontrak Dalam Status Aman</p>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500">Tidak ada kontrak yang overdue maupun expiring soon.</p>
                </div>
            @endif
        </div>

        <!-- Right: Recent Contracts Preview (7 Cols) -->
        <div class="lg:col-span-7 p-6 rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <span>Daftar Kontrak Terbaru</span>
                </h3>
                <a href="{{ route('contracts.index') }}{{ !empty($selectedYear) ? '?tahun=' . $selectedYear : '' }}" class="text-[11px] font-bold text-red-600 dark:text-red-400 hover:underline">
                    Buka Semua ({{ count($contracts) }}) &rarr;
                </a>
            </div>

            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-950/60 text-slate-500 dark:text-slate-400 text-[10px] uppercase font-semibold border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-3 py-2.5">Satker / Nama GC</th>
                            <th class="px-3 py-2.5">Tahapan</th>
                            <th class="px-3 py-2.5">Nilai Win</th>
                            <th class="px-3 py-2.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-sans">
                        @forelse(array_slice($contracts, 0, 5) as $recent)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="px-3 py-2.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="font-mono font-bold text-red-600 dark:text-red-400">{{ $recent['lop'] }}</span>
                                        @if(!empty($recent['id_mytens']))
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-mono text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800">
                                                {{ $recent['id_mytens'] }}
                                            </span>
                                        @endif
                                        @if(!empty($recent['tahun']))
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-mono text-slate-600 dark:text-slate-300 bg-slate-200/50 dark:bg-slate-800">
                                                {{ $recent['tahun'] }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs font-bold text-slate-800 dark:text-white truncate max-w-[200px]">
                                        {{ $recent['satker'] ?: '-' }}
                                    </p>
                                    @if(!empty($recent['nama_gc']))
                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 truncate max-w-[200px]">
                                            🏢 {{ $recent['nama_gc'] }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 whitespace-nowrap">
                                    <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 whitespace-nowrap">
                                        {{ $recent['stage'] }} &bull; {{ $recent['stage_label'] ?? '' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5">
                                    <span class="font-mono font-bold text-slate-900 dark:text-white">{{ $recent['revenue_formatted'] ?? '-' }}</span>
                                    @if(!empty($recent['nilai_bc']) && $recent['nilai_bc'] > 0)
                                        <p class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">
                                            BC: {{ $recent['nilai_bc_formatted'] }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-right">
                                    <a
                                        href="{{ route('contracts.show', $recent['lop']) }}"
                                        class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white text-[11px] font-bold transition-colors inline-block"
                                    >
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-slate-400 dark:text-slate-500">
                                    Tidak ada data kontrak yang sesuai dengan filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const stageChartData = @json($stageChartData);
    const gcChartData = @json($gcChartData);

    // Helpers to detect active theme
    function isDarkTheme() {
        return document.documentElement.classList.contains('dark') || document.documentElement.classList.contains('pink');
    }

    function getThemeTokens() {
        const dark = isDarkTheme();
        return {
            textColor: dark ? '#94a3b8' : '#475569',
            headingColor: dark ? '#f8fafc' : '#0f172a',
            gridColor: dark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)',
            borderColor: dark ? '#0f172a' : '#ffffff',
            tooltipBg: dark ? 'rgba(15, 23, 42, 0.95)' : 'rgba(15, 23, 42, 0.92)',
        };
    }

    function formatCompactRupiah(value) {
        if (value >= 1000000000) {
            return 'Rp ' + (value / 1000000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' M';
        } else if (value >= 1000000) {
            return 'Rp ' + (value / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' Jt';
        } else if (value >= 1000) {
            return 'Rp ' + (value / 1000).toLocaleString('id-ID', { maximumFractionDigits: 0 }) + ' Rb';
        }
        return 'Rp ' + (value || 0).toLocaleString('id-ID');
    }

    let tokens = getThemeTokens();

    // -------------------------------------------------------------
    // 1. STAGE PIPELINE DOUGHNUT CHART
    // -------------------------------------------------------------
    const stageCanvas = document.getElementById('stageDoughnutChart');
    let stageChart = null;

    if (stageCanvas && stageChartData) {
        const stageColors = [
            '#64748B', // F0 - Slate
            '#0284C7', // F1 - Sky
            '#6366F1', // F2 - Indigo
            '#F59E0B', // F3 - Amber
            '#10B981', // F4 - Emerald
        ];

        stageChart = new Chart(stageCanvas, {
            type: 'doughnut',
            data: {
                labels: stageChartData.labels,
                datasets: [{
                    data: stageChartData.counts,
                    backgroundColor: stageColors,
                    hoverBackgroundColor: stageColors,
                    borderColor: tokens.borderColor,
                    borderWidth: 3,
                    hoverOffset: 6,
                    cutout: '74%',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: tokens.tooltipBg,
                        titleColor: '#ffffff',
                        bodyColor: '#e2e8f0',
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 12,
                        boxPadding: 6,
                        usePointStyle: true,
                        callbacks: {
                            label: function(context) {
                                const count = context.raw || 0;
                                const revenue = (stageChartData.revenues_formatted && stageChartData.revenues_formatted[context.dataIndex])
                                    ? stageChartData.revenues_formatted[context.dataIndex]
                                    : 'Rp 0';
                                return ` ${count} LOP • Total: ${revenue}`;
                            }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 750,
                }
            }
        });
    }

    // -------------------------------------------------------------
    // 2. REVENUE PER GROUP COMPANY (GC) HORIZONTAL BAR CHART
    // -------------------------------------------------------------
    const gcCanvas = document.getElementById('gcBarChart');
    let gcChart = null;

    if (gcCanvas && gcChartData && gcChartData.labels && gcChartData.labels.length > 0) {
        const gcColors = [
            '#e11d48', // Primary Telkom Rose
            '#f43f5e',
            '#ef4444',
            '#f97316',
            '#0ea5e9',
            '#8b5cf6',
            '#10b981',
            '#64748b',
        ];

        gcChart = new Chart(gcCanvas, {
            type: 'bar',
            data: {
                labels: gcChartData.labels,
                datasets: [{
                    label: 'Nilai Realisasi Win',
                    data: gcChartData.revenues,
                    backgroundColor: gcChartData.labels.map((_, i) => gcColors[i % gcColors.length]),
                    borderRadius: 8,
                    borderSkipped: false,
                    maxBarThickness: 32,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: {
                            color: tokens.gridColor,
                            drawBorder: false,
                        },
                        ticks: {
                            color: tokens.textColor,
                            font: {
                                family: 'JetBrains Mono, monospace',
                                size: 10,
                            },
                            callback: function(value) {
                                return formatCompactRupiah(value);
                            }
                        }
                    },
                    y: {
                        grid: {
                            display: false,
                            drawBorder: false,
                        },
                        ticks: {
                            color: tokens.textColor,
                            font: {
                                family: 'Inter, sans-serif',
                                size: 11,
                                weight: 'bold',
                            },
                            callback: function(value) {
                                const label = this.getLabelForValue(value) || '';
                                return label.length > 22 ? label.substring(0, 20) + '…' : label;
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: tokens.tooltipBg,
                        titleColor: '#ffffff',
                        bodyColor: '#e2e8f0',
                        borderColor: 'rgba(255, 255, 255, 0.1)',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 12,
                        callbacks: {
                            title: function(context) {
                                return context[0].label;
                            },
                            label: function(context) {
                                const idx = context.dataIndex;
                                const formatted = (gcChartData.revenues_formatted && gcChartData.revenues_formatted[idx])
                                    ? gcChartData.revenues_formatted[idx]
                                    : 'Rp ' + (context.raw || 0).toLocaleString('id-ID');
                                const count = (gcChartData.counts && gcChartData.counts[idx])
                                    ? gcChartData.counts[idx]
                                    : 1;
                                return [
                                    ` Nilai Realisasi Win: ${formatted}`,
                                    ` Jumlah Kontrak: ${count} LOP`
                                ];
                            }
                        }
                    }
                },
                animation: {
                    duration: 800,
                }
            }
        });
    }

    // -------------------------------------------------------------
    // 3. OBSERVE THEME CHANGES (Dark / Light / Pink mode reactive)
    // -------------------------------------------------------------
    const observer = new MutationObserver(function() {
        tokens = getThemeTokens();

        if (stageChart) {
            stageChart.data.datasets[0].borderColor = tokens.borderColor;
            stageChart.options.plugins.tooltip.backgroundColor = tokens.tooltipBg;
            stageChart.update();
        }

        if (gcChart) {
            gcChart.options.scales.x.grid.color = tokens.gridColor;
            gcChart.options.scales.x.ticks.color = tokens.textColor;
            gcChart.options.scales.y.ticks.color = tokens.textColor;
            gcChart.options.plugins.tooltip.backgroundColor = tokens.tooltipBg;
            gcChart.update();
        }
    });

    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class']
    });
});
</script>
@endpush
