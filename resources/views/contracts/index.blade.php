@extends('layouts.app')

@section('title', 'Kontrak & Pipeline')
@section('page_title')
    <span class="text-slate-900 dark:text-white">Manajemen Kontrak & Pipeline</span>
@endsection

@section('content')
<div
    class="space-y-6"
    x-data="{
        viewMode: '{{ $currentView ?? 'table' }}',
        pdfModalOpen: false,
        pdfLop: '',
        pdfCustomer: '',
        pdfLoading: false,
        pdfError: null,
        pdfProxyUrl: null,
        pdfMetadata: null,

        openDocumentViewer(lop, customer) {
            this.pdfLop = lop;
            this.pdfCustomer = customer;
            this.pdfLoading = true;
            this.pdfError = null;
            this.pdfProxyUrl = null;
            this.pdfMetadata = null;
            this.pdfModalOpen = true;

            fetch(`/contracts/${lop}/document/metadata`)
                .then(res => res.json())
                .then(data => {
                    this.pdfLoading = false;
                    if (data.success && data.metadata) {
                        this.pdfMetadata = data.metadata;
                        this.pdfProxyUrl = data.metadata.proxy_url;
                    } else {
                        this.pdfError = data.error || 'Dokumen tidak tersedia atau belum diunggah.';
                    }
                })
                .catch(err => {
                    this.pdfLoading = false;
                    this.pdfError = 'Gagal memeriksa dokumen dari Google Drive.';
                });
        },

        closeDocumentViewer() {
            this.pdfModalOpen = false;
            this.pdfProxyUrl = null;
        }
    }"
>
    <!-- Header Section with View Switcher -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-5 rounded-3xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <svg class="w-6 h-6 text-red-600 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Kontrak Kerja B2B & Pipeline</span>
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Menampilkan total <strong class="text-slate-900 dark:text-white font-mono">{{ count($contracts) }}</strong> kontrak kerja B2B (dari total {{ count($allContracts) }} data di Google Sheets).
            </p>
        </div>

        <!-- View Mode Switcher (Tab Buttons) -->
        <div class="flex items-center gap-2">
            <div class="p-1 rounded-2xl bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center">
                <button
                    type="button"
                    @click="viewMode = 'table'"
                    :class="viewMode === 'table' ? 'bg-red-600 text-white shadow-md font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
                    class="px-3.5 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    <span>Tabel Data</span>
                </button>
                <button
                    type="button"
                    @click="viewMode = 'kanban'"
                    :class="viewMode === 'kanban' ? 'bg-red-600 text-white shadow-md font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
                    class="px-3.5 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                    </svg>
                    <span>Papan Kanban (F0–F4)</span>
                </button>
            </div>

            <a
                href="{{ route('contracts.create') }}"
                class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-md shadow-red-600/25 transition-all flex items-center gap-1.5"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>+ Tambah</span>
            </a>
        </div>
    </div>

    <!-- Error Alert if any -->
    @if ($errorMessage)
        <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-700 dark:text-amber-300 text-xs flex items-center gap-2.5">
            <svg class="w-5 h-5 shrink-0 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <span>{{ $errorMessage }}</span>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- SEARCH & ADVANCED FILTERS (Applies to both Table and Kanban views) -->
    <!-- ========================================================================= -->
    <div class="p-4 rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-lg" 
         x-data="{ 
             filterDrawerOpen: {{ ($activeFilterCount ?? 0) > 0 ? 'true' : 'false' }},
             isTyping: false,
             debounceTimer: null,
             onSearchInput(e) {
                 this.isTyping = true;
                 clearTimeout(this.debounceTimer);
                 this.debounceTimer = setTimeout(() => {
                     this.isTyping = false;
                     e.target.closest('form').submit();
                 }, 500);
             }
         }"
         x-init="
             const urlParams = new URLSearchParams(window.location.search);
             if (urlParams.has('q') && urlParams.get('q') !== '') {
                 const qInput = $el.querySelector('input[name=\'q\']');
                 if (qInput) {
                     qInput.focus();
                     qInput.setSelectionRange(qInput.value.length, qInput.value.length);
                 }
             }
         "
    >
        <form method="GET" action="{{ route('contracts.index') }}" class="space-y-3">
            <input type="hidden" name="view" :value="viewMode">

            <div class="flex flex-col sm:flex-row gap-3">
                <!-- Search Input with 0.5-Second Debounce Auto-Filter -->
                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input
                        type="text"
                        name="q"
                        value="{{ $filters['q'] ?? '' }}"
                        placeholder="Cari berdasarkan LOP, No Kontrak, Pelanggan, Satker, atau Layanan... (otomatis memfilter 0.5 dtk)"
                        class="w-full pl-10 pr-28 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors"
                        @input="onSearchInput($event)"
                        @keydown.enter="clearTimeout(debounceTimer); $el.closest('form').submit()"
                    >
                    <!-- Visual Debounce Countdown / Filtering Indicator -->
                    <div x-show="isTyping" style="display: none;" class="absolute inset-y-0 right-0 pr-3 flex items-center gap-1.5 pointer-events-none">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-red-600 animate-ping"></span>
                        <span class="text-[11px] font-semibold text-red-600 dark:text-red-400">Menyaring...</span>
                    </div>
                </div>

                <!-- Filter Toggle & Submit Buttons -->
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="filterDrawerOpen = !filterDrawerOpen"
                        :class="filterDrawerOpen ? 'bg-red-50 dark:bg-red-950/40 border-red-300 dark:border-red-900 text-red-700 dark:text-red-300' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700'"
                        class="px-3.5 py-2.5 rounded-xl border text-xs font-semibold flex items-center gap-1.5 transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        <span>Filter Lanjutan</span>
                        @if(($activeFilterCount ?? 0) > 0)
                            <span class="px-1.5 py-0.2 rounded-full bg-red-600 text-white text-[10px] font-bold">{{ $activeFilterCount }}</span>
                        @endif
                        <svg class="w-3.5 h-3.5 transition-transform" :class="filterDrawerOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <button
                        type="submit"
                        @click="clearTimeout(debounceTimer)"
                        class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-md shadow-red-600/20 transition-all cursor-pointer"
                    >
                        Terapkan
                    </button>

                    @if(($activeFilterCount ?? 0) > 0)
                        <a
                            href="{{ route('contracts.index', ['view' => $currentView]) }}"
                            class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-colors"
                            title="Reset Semua Filter"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </a>
                    @endif
                </div>
            </div>

            <!-- Expanded Filter Drawer -->
            <div x-show="filterDrawerOpen" style="display: none;" class="pt-3 border-t border-slate-200 dark:border-slate-800/80 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-7 gap-3">
                <!-- Tahun Filter -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Tahun</label>
                    <select name="tahun" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
                        <option value="">Semua Tahun</option>
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}" {{ ($filters['tahun'] ?? '') == $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Nama GC Filter -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Nama GC</label>
                    <select name="nama_gc" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
                        <option value="">Semua GC</option>
                        @foreach($availableGCs as $gc)
                            <option value="{{ $gc }}" {{ ($filters['nama_gc'] ?? '') === $gc ? 'selected' : '' }}>{{ $gc }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Stage Filter -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Tahapan Pipeline</label>
                    <select name="stage" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
                        <option value="">Semua Tahapan</option>
                        <option value="F0" {{ ($filters['stage'] ?? '') === 'F0' ? 'selected' : '' }}>F0 — Lead</option>
                        <option value="F1" {{ ($filters['stage'] ?? '') === 'F1' ? 'selected' : '' }}>F1 — Opportunity</option>
                        <option value="F2" {{ ($filters['stage'] ?? '') === 'F2' ? 'selected' : '' }}>F2 — Quote</option>
                        <option value="F3" {{ ($filters['stage'] ?? '') === 'F3' ? 'selected' : '' }}>F3 — Bidding</option>
                        <option value="F4" {{ ($filters['stage'] ?? '') === 'F4' ? 'selected' : '' }}>F4 — Negotiation</option>
                    </select>
                </div>

                <!-- Satker Filter -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Satker</label>
                    <select name="satker" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
                        <option value="">Semua Satker</option>
                        @foreach($availableSatkers as $s)
                            <option value="{{ $s }}" {{ ($filters['satker'] ?? '') === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Expiration Status Filter -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Status Kedaluwarsa</label>
                    <select name="expiration_status" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
                        <option value="">Semua Status</option>
                        <option value="ACTIVE" {{ ($filters['expiration_status'] ?? '') === 'ACTIVE' ? 'selected' : '' }}>🟢 ACTIVE (&gt;60 hari)</option>
                        <option value="EXPIRING_SOON" {{ ($filters['expiration_status'] ?? '') === 'EXPIRING_SOON' ? 'selected' : '' }}>🟡 EXPIRING SOON (0–60 hari)</option>
                        <option value="OVERDUE" {{ ($filters['expiration_status'] ?? '') === 'OVERDUE' ? 'selected' : '' }}>🔴 OVERDUE (&lt;0 hari)</option>
                    </select>
                </div>

                <!-- Status Kontrak Filter -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Status Kontrak</label>
                    <select name="status_kontrak" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
                        <option value="">Semua Status</option>
                        <option value="BERJALAN" {{ ($filters['status_kontrak'] ?? '') === 'BERJALAN' ? 'selected' : '' }}>🔵 Kontrak Berjalan</option>
                        <option value="SELESAI" {{ ($filters['status_kontrak'] ?? '') === 'SELESAI' ? 'selected' : '' }}>🟢 Kontrak Selesai</option>
                    </select>
                </div>

                <!-- SP/PO Status Filter -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Status SP / PO</label>
                    <select name="sp_po" class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500">
                        <option value="">Semua SP/PO</option>
                        <option value="AVAILABLE" {{ ($filters['sp_po'] ?? '') === 'AVAILABLE' ? 'selected' : '' }}>AVAILABLE (Tersedia)</option>
                        <option value="MISSING" {{ ($filters['sp_po'] ?? '') === 'MISSING' ? 'selected' : '' }}>MISSING (Belum Ada)</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 1: FULL CONTRACT DATA TABLE VIEW -->
    <!-- ========================================================================= -->
    <div x-show="viewMode === 'table'" class="space-y-4">

        <!-- Full Contract Table -->
        <div class="rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl overflow-hidden">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-950/80 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800 uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="px-4 py-3.5">LOP & Identitas</th>
                            <th class="px-4 py-3.5">Satker / Nama GC</th>
                            <th class="px-4 py-3.5">Layanan</th>
                            <th class="px-4 py-3.5">Tahapan & Status</th>
                            <th class="px-4 py-3.5">Nilai Realisasi Win</th>
                            <th class="px-4 py-3.5">Masa Berlaku</th>
                            <th class="px-4 py-3.5">SP/PO</th>
                            <th class="px-4 py-3.5">Realisasi Billcomp</th>
                            <th class="px-4 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-sans">
                        @forelse($contracts as $contract)
                            @php
                                $expStatus = $contract['expiration_status'] ?? 'ACTIVE';
                                $expBadge = match($expStatus) {
                                    'OVERDUE' => 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20 font-extrabold',
                                    'EXPIRING_SOON' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20 font-bold',
                                    default => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                                };
                                $stageKey = $contract['stage'] ?? 'F0';
                                $stageColor = match($stageKey) {
                                    'F0' => 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700',
                                    'F1' => 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/20',
                                    'F2' => 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20',
                                    'F3' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                                    'F4' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                                    default => 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700',
                                };
                            @endphp
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <!-- LOP & Identitas -->
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="font-mono font-bold text-red-600 dark:text-red-400 text-xs">{{ $contract['lop'] }}</span>
                                        @if(!empty($contract['id_mytens']))
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-mono text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                                {{ $contract['id_mytens'] }}
                                            </span>
                                        @endif
                                        @if(!empty($contract['tahun']))
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-mono text-slate-600 dark:text-slate-300 bg-slate-200/60 dark:bg-slate-800">
                                                {{ $contract['tahun'] }}
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate max-w-[170px] font-mono mt-0.5">
                                        {{ $contract['contract_number'] ?: '-' }}
                                    </p>
                                </td>

                                <!-- Satker & GC -->
                                <td class="px-4 py-3.5">
                                    <p class="font-bold text-slate-900 dark:text-white truncate max-w-[220px]">
                                        {{ $contract['satker'] ?: '-' }}
                                    </p>
                                    @if(!empty($contract['nama_gc']))
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate max-w-[220px] mt-0.5">
                                            🏢 {{ $contract['nama_gc'] }}
                                        </p>
                                    @else
                                        <p class="text-[11px] text-slate-400 dark:text-slate-500 truncate max-w-[220px] mt-0.5">
                                            -
                                        </p>
                                    @endif
                                </td>

                                <!-- Layanan -->
                                <td class="px-4 py-3.5">
                                    <span class="text-slate-700 dark:text-slate-300">{{ $contract['service'] ?: '-' }}</span>
                                </td>

                                <!-- Tahapan Pipeline & Status Kontrak -->
                                <td class="px-4 py-3.5 whitespace-nowrap">
                                    <div class="flex flex-col gap-1 items-start">
                                        <span class="inline-block px-2.5 py-1 rounded-md text-[10px] font-bold font-mono border whitespace-nowrap {{ $stageColor }}">
                                            {{ $contract['stage'] }} &bull; {{ $contract['stage_label'] ?? '' }}
                                        </span>
                                        @if(($contract['status_kontrak'] ?? 'BERJALAN') === 'SELESAI')
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 whitespace-nowrap">
                                                <span>Selesai</span>
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Nilai Kontrak (Realisasi Win) -->
                                <td class="px-4 py-3.5 font-mono">
                                    <span class="font-bold text-slate-900 dark:text-white">{{ $contract['revenue_formatted'] }}</span>
                                    @if(!empty($contract['nilai_bc']) && $contract['nilai_bc'] > 0)
                                        <p class="text-[10px] text-slate-400 font-normal">
                                            BC: {{ $contract['nilai_bc_formatted'] }}
                                        </p>
                                    @endif
                                </td>

                                <!-- Masa Berlaku & Status Kedaluwarsa -->
                                <td class="px-4 py-3.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono border {{ $expBadge }}">
                                        {{ $contract['days_remaining'] ?? 0 }} hari
                                    </span>
                                    <p class="text-[10px] text-slate-500 mt-1 font-mono">s/d {{ $contract['end_date'] ?: '-' }}</p>
                                </td>

                                <!-- Status SP/PO -->
                                <td class="px-4 py-3.5">
                                    @if(($contract['sp_po'] ?? '') === 'AVAILABLE')
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span>Ada</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-600 dark:text-rose-400">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                            <span>Belum</span>
                                        </span>
                                    @endif
                                </td>

                                <!-- Realisasi Billcomp -->
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 bg-slate-200 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                            <div class="bg-emerald-500 h-full rounded-full" style="width: {{ $contract['billcomp_percentage'] ?? 0 }}%"></div>
                                        </div>
                                        <span class="font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300">{{ $contract['billcomp_percentage'] ?? 0 }}%</span>
                                    </div>
                                    <p class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $contract['realized_revenue_formatted'] ?? 'Rp 0' }}</p>
                                </td>

                                <!-- Aksi -->
                                <td class="px-4 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- PDF Preview Button if doc exists -->
                                        @if(!empty($contract['document_reference']))
                                            <button
                                                type="button"
                                                @click="openDocumentViewer('{{ $contract['lop'] }}', '{{ addslashes($contract['customer']) }}')"
                                                class="p-1.5 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 border border-red-500/20 transition-colors cursor-pointer"
                                                title="Lihat Dokumen Google Drive"
                                            >
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                </svg>
                                            </button>
                                        @endif

                                        <!-- Quick Complete Button for Overdue running contracts -->
                                        @if(($contract['is_overdue'] ?? false) && ($contract['status_kontrak'] ?? 'BERJALAN') !== 'SELESAI')
                                            <form action="{{ route('contracts.complete', $contract['lop']) }}" method="POST" onsubmit="return confirm('Selesaikan kontrak {{ $contract['lop'] }} secara manual?')" class="inline">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="p-1.5 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 transition-colors cursor-pointer"
                                                    title="Selesaikan Kontrak (Overdue)"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif

                                        <a
                                            href="{{ route('contracts.show', $contract['lop']) }}"
                                            class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-colors"
                                            title="Buka Detail Lengkap"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </a>

                                        <a
                                            href="{{ route('contracts.edit', $contract['lop']) }}"
                                            class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-colors"
                                            title="Edit Data Kontrak"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-12 text-center text-slate-400 dark:text-slate-500 space-y-2">
                                    <svg class="w-8 h-8 mx-auto text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Tidak ada kontrak kerja yang cocok dengan filter pencarian.</p>
                                    <p class="text-[11px] text-slate-400 dark:text-slate-500">Coba atur ulang kata kunci atau filter status.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: PIPELINE KANBAN BOARD (F0–F4) -->
    <!-- ========================================================================= -->
    <div x-show="viewMode === 'kanban'" style="display: none;" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 items-start">
            @foreach($stages as $stageKey => $stageData)
                @php
                    $headerColor = match($stageKey) {
                        'F0' => 'border-t-slate-400 dark:border-t-slate-500',
                        'F1' => 'border-t-sky-500',
                        'F2' => 'border-t-indigo-500',
                        'F3' => 'border-t-amber-500',
                        'F4' => 'border-t-emerald-500',
                        default => 'border-t-slate-400 dark:border-t-slate-600',
                    };
                @endphp
                <div class="rounded-3xl bg-slate-50 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 border-t-4 {{ $headerColor }} shadow-sm dark:shadow-xl flex flex-col max-h-[75vh]">
                    <!-- Column Header -->
                    <div class="p-4 border-b border-slate-200 dark:border-slate-800/80 bg-white dark:bg-slate-950/40 rounded-t-3xl">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-extrabold text-slate-900 dark:text-white font-mono">{{ $stageKey }} &bull; {{ $stageData['label'] }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono">
                                {{ $stageData['count'] }}
                            </span>
                        </div>
                        <p class="text-[11px] font-bold text-red-600 dark:text-red-400 font-mono mt-1 truncate">{{ $stageData['total_revenue_formatted'] }}</p>
                    </div>

                    <!-- Column Cards List -->
                    <div class="p-3 space-y-3 overflow-y-auto custom-scrollbar flex-1">
                        @forelse($stageData['contracts'] as $card)
                            @php
                                $cardExpStatus = $card['expiration_status'] ?? 'ACTIVE';
                                $cardBadge = match($cardExpStatus) {
                                    'OVERDUE' => 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
                                    'EXPIRING_SOON' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                                    default => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                                };
                            @endphp
                            <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800/90 hover:border-red-500/40 transition-all shadow-sm group">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="font-mono text-xs font-bold text-red-600 dark:text-red-400">{{ $card['lop'] }}</span>
                                        @if(!empty($card['id_mytens']))
                                            <span class="px-1.5 py-0.2 rounded text-[8px] font-mono text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800">
                                                {{ $card['id_mytens'] }}
                                            </span>
                                        @endif
                                        @if(!empty($card['tahun']))
                                            <span class="px-1.5 py-0.2 rounded text-[8px] font-mono text-slate-600 dark:text-slate-300 bg-slate-200/60 dark:bg-slate-800">
                                                {{ $card['tahun'] }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-1">
                                        @if(($card['status_kontrak'] ?? 'BERJALAN') === 'SELESAI')
                                            <span class="px-1.5 py-0.2 rounded text-[8px] font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30">
                                                Selesai
                                            </span>
                                        @endif
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-mono font-bold border {{ $cardBadge }}">
                                            {{ $card['days_remaining'] ?? 0 }}h
                                        </span>
                                    </div>
                                </div>

                                <h4 class="text-xs font-bold text-slate-900 dark:text-white mt-1.5 line-clamp-2 leading-tight group-hover:text-red-600 dark:group-hover:text-red-400 transition-colors">
                                    {{ $card['satker'] ?: '-' }}
                                </h4>

                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1 truncate">
                                    {{ !empty($card['nama_gc']) ? '🏢 ' . $card['nama_gc'] : '-' }}
                                </p>

                                <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
                                    <span class="text-xs font-mono font-extrabold text-slate-900 dark:text-white">{{ $card['revenue_formatted'] }}</span>
                                    <a
                                        href="{{ route('contracts.show', $card['lop']) }}"
                                        class="p-1 rounded bg-slate-100 dark:bg-slate-900 hover:bg-slate-200 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors"
                                        title="Buka Detail"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="py-8 text-center text-slate-400 dark:text-slate-600 text-xs">
                                Kosong
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- GOOGLE DRIVE PDF PREVIEW MODAL (Alpine.js) -->
    <!-- ========================================================================= -->
    <div
        x-show="pdfModalOpen"
        style="display: none;"
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4"
        @keydown.escape.window="closeDocumentViewer()"
    >
        <div
            @click.away="closeDocumentViewer()"
            class="w-full max-w-4xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
        >
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-600 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        <span>Pratinjau Dokumen Google Drive &bull; <span x-text="pdfLop" class="font-mono text-red-600 dark:text-red-400"></span></span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400" x-text="pdfCustomer"></p>
                </div>

                <button
                    type="button"
                    @click="closeDocumentViewer()"
                    class="p-2 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Modal Content -->
            <div class="flex-1 p-6 overflow-y-auto bg-slate-50/50 dark:bg-slate-950/60 min-h-[450px] flex items-center justify-center">
                <!-- Loading State -->
                <div x-show="pdfLoading" class="text-center space-y-3">
                    <div class="w-10 h-10 border-4 border-red-500/20 border-t-red-500 rounded-full animate-spin mx-auto"></div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">Memeriksa referensi dokumen Google Drive...</p>
                </div>

                <!-- Error State -->
                <div x-show="!pdfLoading && pdfError" class="text-center max-w-md space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-600 dark:text-rose-400 mx-auto">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">Dokumen Tidak Dapat Dibuka</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400" x-text="pdfError"></p>
                </div>

                <!-- Loaded Iframe State -->
                <template x-if="!pdfLoading && !pdfError && pdfProxyUrl">
                    <div class="w-full h-[600px] rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950">
                        <iframe :src="pdfProxyUrl" class="w-full h-full border-0"></iframe>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection
