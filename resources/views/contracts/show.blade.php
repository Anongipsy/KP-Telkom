@extends('layouts.app')

@section('title', 'Detail Kontrak ' . ($contract['lop'] ?? ''))
@section('page_title')
    <span class="text-slate-900 dark:text-white">Detail Kontrak &bull; <span class="font-mono text-red-600 dark:text-red-400">{{ $contract['lop'] }}</span></span>
@endsection

@section('content')
<div class="space-y-6 max-w-5xl mx-auto" x-data="docViewer()">
    <!-- Navigation / Breadcrumbs -->
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
            <a href="{{ route('contracts.index') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span>Kontrak & Pipeline</span>
            </a>
            <span>/</span>
            <span class="text-slate-900 dark:text-slate-200 font-semibold font-mono">{{ $contract['lop'] }}</span>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2.5">
            @if(($contract['is_overdue'] ?? false) && ($contract['status_kontrak'] ?? 'BERJALAN') !== 'SELESAI')
                <form action="{{ route('contracts.complete', $contract['lop']) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menandai kontrak ini sebagai SELESAI?')">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-md shadow-emerald-600/25 transition-all cursor-pointer"
                        title="Selesaikan kontrak overdue secara manual"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Selesaikan Kontrak</span>
                    </button>
                </form>
            @endif

            <a
                href="{{ route('contracts.edit', $contract['lop']) }}"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-white text-xs font-bold shadow-md shadow-red-600/25 transition-all"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                <span>Edit Kontrak</span>
            </a>
        </div>
    </div>

    <!-- Contract Header Card -->
    <div class="rounded-3xl bg-white dark:bg-gradient-to-r dark:from-slate-900 dark:via-slate-900 dark:to-slate-800 border border-slate-200 dark:border-slate-800 p-6 sm:p-8 shadow-sm dark:shadow-xl">
        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    <span class="px-3 py-1 rounded-lg font-mono text-xs font-bold bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-red-600 dark:text-red-400">
                        {{ $contract['lop'] }}
                    </span>
                    @if(!empty($contract['id_mytens']))
                        <span class="px-2.5 py-1 rounded-lg font-mono text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            ID: {{ $contract['id_mytens'] }}
                        </span>
                    @endif
                    @if(!empty($contract['tahun']))
                        <span class="px-2.5 py-1 rounded-lg font-mono text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                            Tahun: {{ $contract['tahun'] }}
                        </span>
                    @endif
                    <x-stage-badge :stage="$contract['stage']" />
                    <x-status-badge type="status_kontrak" :value="$contract['status_kontrak'] ?? 'BERJALAN'" />
                    <x-status-badge type="expiration" :value="$contract['expiration_status']" :days="$contract['days_remaining']" />
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    {{ !empty($contract['satker']) ? $contract['satker'] : ($contract['customer'] ?? '-') }}
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1 flex flex-wrap items-center gap-2">
                    @if(!empty($contract['nama_gc']))
                        <span>Nama GC: <strong class="text-slate-800 dark:text-slate-200">🏢 {{ $contract['nama_gc'] }}</strong></span>
                        <span>&bull;</span>
                    @endif
                    <span>Layanan: <strong class="text-slate-800 dark:text-slate-200">{{ $contract['service'] ?? '-' }}</strong></span>
                </p>
            </div>

            <!-- Financial Summary Box -->
            <div class="bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:text-right min-w-[220px]">
                <span class="text-[10px] text-slate-500 uppercase tracking-wider block font-bold">Nilai Realisasi Win</span>
                <span class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white font-mono block mt-0.5">
                    {{ $contract['revenue_formatted'] }}
                </span>
                <span class="text-xs text-slate-500 dark:text-slate-400 block mt-1">
                    Realized: <strong class="text-emerald-600 dark:text-emerald-400 font-mono">{{ $contract['realized_revenue_formatted'] }}</strong> ({{ $contract['billcomp_percentage'] }}%)
                </span>
                @if(!empty($contract['nilai_bc']) && $contract['nilai_bc'] > 0)
                    <span class="text-[11px] text-slate-500 dark:text-slate-400 block mt-1">
                        Nilai BC: <strong class="text-slate-800 dark:text-slate-200 font-mono">{{ $contract['nilai_bc_formatted'] }}</strong>
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- 1. General & Contract Information -->
        <div class="p-6 rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-lg space-y-4">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Informasi Umum Kontrak</span>
            </h2>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div>
                    <dt class="text-slate-500 font-medium">Nomor LOP</dt>
                    <dd class="mt-1 font-mono font-bold text-red-600 dark:text-red-400">{{ $contract['lop'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 font-medium">ID MyTens</dt>
                    <dd class="mt-1 font-mono text-slate-800 dark:text-slate-200">{{ !empty($contract['id_mytens']) ? $contract['id_mytens'] : '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 font-medium">Tahun Kontrak</dt>
                    <dd class="mt-1 font-mono text-slate-800 dark:text-slate-200">{{ !empty($contract['tahun']) ? $contract['tahun'] : '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 font-medium">Nomor Kontrak</dt>
                    <dd class="mt-1 font-mono text-slate-800 dark:text-slate-200">{{ !empty($contract['contract_number']) ? $contract['contract_number'] : '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 font-medium">Nama GC (Group Company)</dt>
                    <dd class="mt-1 font-bold text-slate-900 dark:text-white">{{ !empty($contract['nama_gc']) ? $contract['nama_gc'] : '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 font-medium">Satker</dt>
                    <dd class="mt-1 font-bold text-slate-900 dark:text-white">{{ !empty($contract['satker']) ? $contract['satker'] : '-' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-slate-500 font-medium">Judul Proyek</dt>
                    <dd class="mt-1 font-bold text-slate-900 dark:text-white">{{ !empty($contract['judul_proyek']) ? $contract['judul_proyek'] : ($contract['customer'] ?? '-') }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-slate-500 font-medium">Deskripsi Layanan</dt>
                    <dd class="mt-1 font-bold text-slate-900 dark:text-slate-100">{{ $contract['service'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 font-medium">Tahapan Pipeline</dt>
                    <dd class="mt-1"><x-stage-badge :stage="$contract['stage'] ?? 'F0'" /></dd>
                </div>
                <div>
                    <dt class="text-slate-500 font-medium">Status Kontrak</dt>
                    <dd class="mt-1"><x-status-badge type="status_kontrak" :value="$contract['status_kontrak'] ?? 'BERJALAN'" /></dd>
                </div>
                <div>
                    <dt class="text-slate-500 font-medium">Posisi di Spreadsheet</dt>
                    <dd class="mt-1 font-mono text-slate-500 dark:text-slate-400">Baris {{ $contract['_row_index'] ?? '-' }}</dd>
                </div>
            </dl>
        </div>

        <!-- 2. Timeline & Expiration Monitoring -->
        <div class="p-6 rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-lg space-y-4">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
                <svg class="w-4 h-4 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span>Timeline & Masa Berlaku</span>
            </h2>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div>
                    <dt class="text-slate-500 font-medium">Start Date</dt>
                    <dd class="mt-1 font-mono text-slate-800 dark:text-slate-200">{{ !empty($contract['start_date']) ? $contract['start_date'] : '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 font-medium">End Date</dt>
                    <dd class="mt-1 font-mono text-slate-800 dark:text-slate-200">{{ !empty($contract['end_date']) ? $contract['end_date'] : '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 font-medium">Estimasi Durasi Pemakaian</dt>
                    <dd class="mt-1 font-bold text-slate-800 dark:text-slate-200">{{ !empty($contract['durasi_bulan']) ? $contract['durasi_bulan'] . ' Bulan' : '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 font-medium">Estimasi Bulan BC</dt>
                    <dd class="mt-1 font-mono text-slate-800 dark:text-slate-200">{{ !empty($contract['estimasi_bulan_bc']) ? $contract['estimasi_bulan_bc'] : '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 font-medium">Status Masa Berlaku</dt>
                    <dd class="mt-1">
                        <x-status-badge type="expiration" :value="$contract['expiration_status']" :days="$contract['days_remaining']" />
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500 font-medium">Sisa Waktu</dt>
                    <dd class="mt-1 font-bold {{ ($contract['days_remaining'] ?? 0) < 0 ? 'text-rose-600 dark:text-red-400' : (($contract['days_remaining'] ?? 0) <= 60 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">
                        @if($contract['days_remaining'] !== null)
                            {{ $contract['days_remaining'] >= 0 ? $contract['days_remaining'] . ' hari tersisa' : abs($contract['days_remaining']) . ' hari terlewat' }}
                        @else
                            -
                        @endif
                    </dd>
                </div>
            </dl>

            <!-- Early Warning Banner -->
            @if(($contract['expiration_status'] ?? '') === 'EXPIRING_SOON')
                <div class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/25 text-amber-700 dark:text-amber-300 text-xs flex items-center gap-2.5">
                    <svg class="w-4 h-4 shrink-0 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <span>Perhatian: Masa berlaku kontrak &lt; 60 hari. Segera tindak lanjuti proses renewal.</span>
                </div>
            @elseif(($contract['expiration_status'] ?? '') === 'OVERDUE')
                @if(($contract['status_kontrak'] ?? 'BERJALAN') === 'SELESAI')
                    <div class="p-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/25 text-emerald-800 dark:text-emerald-300 text-xs flex items-center justify-between gap-2.5">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Kontrak telah melewati masa berlaku dan <strong>telah diselesaikan secara manual</strong> oleh Account Manager (AM).</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-md bg-emerald-600 text-white text-[10px] font-bold shrink-0">Tuntas</span>
                    </div>
                @else
                    <div class="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/25 text-rose-700 dark:text-red-300 text-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 shrink-0 text-rose-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Peringatan: Kontrak telah melewati masa berlaku (Overdue)! Segera selesaikan jika kewajiban telah tuntas.</span>
                        </div>
                        <form action="{{ route('contracts.complete', $contract['lop']) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menandai kontrak ini sebagai SELESAI?')">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-sm transition-all cursor-pointer shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Selesaikan Kontrak</span>
                            </button>
                        </form>
                    </div>
                @endif
            @endif
        </div>

        <!-- 3. Financial & Billing Information -->
        <div class="p-6 rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-lg space-y-4">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
                <svg class="w-4 h-4 text-emerald-500 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Finansial & Penagihan</span>
            </h2>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div>
                    <dt class="text-slate-500 font-medium">Nilai Realisasi Win</dt>
                    <dd class="mt-1 font-mono font-bold text-slate-900 dark:text-white text-sm">{{ $contract['revenue_formatted'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 font-medium">Nilai BC (Per Bulan)</dt>
                    <dd class="mt-1 font-mono font-bold text-slate-800 dark:text-slate-200 text-sm">{{ $contract['nilai_bc_formatted'] ?? '-' }}</dd>
                </div>
                @if(!empty($contract['estimasi_nilai_proyek']) && $contract['estimasi_nilai_proyek'] > 0)
                    <div>
                        <dt class="text-slate-500 font-medium">Estimasi Nilai Proyek</dt>
                        <dd class="mt-1 font-mono font-bold text-slate-600 dark:text-slate-400">{{ $contract['estimasi_nilai_proyek_formatted'] ?? '-' }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-slate-500 font-medium">Status Invoice</dt>
                    <dd class="mt-1"><x-status-badge type="invoice" :value="$contract['invoice_status'] ?? 'UNBILLED'" /></dd>
                </div>
                <div>
                    <dt class="text-slate-500 font-medium">Status Billcomp</dt>
                    <dd class="mt-1"><x-status-badge type="billcomp" :value="$contract['billcomp_status'] ?? 'NOT_COMPLETE'" /></dd>
                </div>
                <div>
                    <dt class="text-slate-500 font-medium">Persentase Billcomp</dt>
                    <dd class="mt-1 font-bold text-slate-900 dark:text-slate-200">{{ $contract['billcomp_percentage'] ?? 0 }}%</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-slate-500 font-medium">Realized Revenue</dt>
                    <dd class="mt-1 font-mono font-bold text-emerald-600 dark:text-emerald-400 text-sm">{{ $contract['realized_revenue_formatted'] ?? '-' }}</dd>
                </div>
            </dl>

            <!-- Billcomp Progress Bar -->
            <div class="space-y-1.5 pt-2">
                <div class="w-full bg-slate-100 dark:bg-slate-950 rounded-full h-2 overflow-hidden border border-slate-200 dark:border-slate-800">
                    <div
                        class="h-2 rounded-full transition-all duration-300 {{ ($contract['billcomp_percentage'] ?? 0) >= 100 ? 'bg-emerald-500' : 'bg-sky-500' }}"
                        style="width: {{ min(100, (int) ($contract['billcomp_percentage'] ?? 0)) }}%"
                    ></div>
                </div>
            </div>
        </div>

        <!-- 4. Administrative & Document Reference -->
        <div class="p-6 rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-lg space-y-4">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
                <svg class="w-4 h-4 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                </svg>
                <span>Administrasi & Dokumen</span>
            </h2>

            <dl class="space-y-4 text-xs">
                <div>
                    <dt class="text-slate-500 font-medium">Status SP / PO</dt>
                    <dd class="mt-1"><x-status-badge type="sp_po" :value="$contract['sp_po']" /></dd>
                </div>

                <div>
                    <dt class="text-slate-500 font-medium">Referensi Dokumen (Google Drive)</dt>
                    <dd class="mt-1">
                        @if(!empty($contract['document_reference']))
                            <div class="space-y-2.5">
                                <div class="flex items-center gap-2 p-2.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800">
                                    <svg class="w-4 h-4 text-red-600 dark:text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="font-mono text-slate-700 dark:text-slate-300 truncate text-[11px] flex-1">{{ $contract['document_reference'] }}</span>
                                </div>

                                <!-- View Document Button (PRD FR-12, FR-13) -->
                                <button
                                    type="button"
                                    @click="openModal()"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-white font-bold text-xs shadow-md shadow-red-600/20 transition-all cursor-pointer"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    <span>Lihat Dokumen Kontrak (PDF/Image)</span>
                                </button>
                            </div>
                        @else
                            <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-400 dark:text-slate-500 italic text-[11px] flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-400 dark:text-slate-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Belum ada referensi dokumen Google Drive. Silakan edit kontrak untuk menambahkan file ID.</span>
                            </div>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- PDF & DOCUMENT VIEWER MODAL (PRD FR-12, FR-13) -->
    <div
        x-show="modalOpen"
        x-cloak
        @keydown.escape.window="closeModal()"
        class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-3 sm:p-6"
        style="display: none;"
    >
        <!-- Modal Backdrop -->
        <div
            x-show="modalOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="closeModal()"
            class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm"
        ></div>

        <!-- Modal Dialog Content -->
        <div
            x-show="modalOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            class="relative w-full max-w-5xl h-[88vh] max-h-[850px] bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl shadow-2xl flex flex-col overflow-hidden z-10"
        >
            <!-- Modal Header -->
            <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/80 flex items-center justify-between gap-4">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="p-1.5 rounded-lg bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </span>
                    <div class="truncate">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white truncate" x-text="metadata ? metadata.name : 'Dokumen Kontrak: {{ $contract['lop'] }}'"></h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-1.5 truncate">
                            <span class="font-mono text-red-600 dark:text-red-400 font-bold">{{ $contract['lop'] }}</span>
                            <span>&bull;</span>
                            <span>{{ $contract['customer'] }}</span>
                            <template x-if="metadata && metadata.size_formatted">
                                <span class="text-slate-400" x-text="'(' + metadata.size_formatted + ')'"></span>
                            </template>
                        </p>
                    </div>
                </div>

                <!-- Modal Actions (Open externally / Close) -->
                <div class="flex items-center gap-2 shrink-0">
                    <template x-if="metadata && metadata.proxy_url">
                        <a
                            :href="metadata.proxy_url"
                            target="_blank"
                            class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-colors text-xs font-semibold flex items-center gap-1.5"
                            title="Buka di Tab Baru"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            <span class="hidden sm:inline">Tab Baru</span>
                        </a>
                    </template>

                    <button
                        type="button"
                        @click="closeModal()"
                        class="p-2 rounded-xl bg-slate-100 hover:bg-red-500/10 dark:bg-slate-800 dark:hover:bg-red-500/20 text-slate-500 hover:text-red-600 dark:hover:text-red-400 transition-colors cursor-pointer"
                        title="Tutup Modal (Esc)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Body (Dynamic States) -->
            <div class="flex-1 bg-slate-50/50 dark:bg-slate-950 relative overflow-hidden flex items-center justify-center p-4">
                <!-- 1. LOADING STATE (PRD FR-13) -->
                <div x-show="loading" class="text-center space-y-3">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-red-600/10 text-red-600 dark:text-red-500 border border-red-500/20">
                        <svg class="animate-spin w-6 h-6" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">Memverifikasi Dokumen Google Drive</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Mengambil metadata dan menyiapkan preview dokumen...</p>
                    </div>
                </div>

                <!-- 2. LOADED STATE: PDF/IMAGE PREVIEW (PRD FR-13) -->
                <template x-if="!loading && status === 'available' && metadata">
                    <div class="w-full h-full flex flex-col items-center justify-center">
                        <template x-if="metadata.is_pdf || metadata.is_previewable">
                            <iframe
                                :src="metadata.proxy_url"
                                class="w-full h-full border-0 rounded-2xl bg-white dark:bg-slate-900 shadow-inner"
                                allow="autoplay"
                            ></iframe>
                        </template>

                        <template x-if="metadata.is_image && !metadata.is_pdf">
                            <div class="w-full h-full flex items-center justify-center p-4 overflow-auto">
                                <img
                                    :src="metadata.proxy_url"
                                    :alt="metadata.name"
                                    class="max-w-full max-h-full object-contain rounded-2xl shadow-lg"
                                />
                            </div>
                        </template>
                    </div>
                </template>

                <!-- 3. FILE UNAVAILABLE STATE (PRD FR-13) -->
                <div x-show="!loading && status === 'not_found'" class="text-center max-w-md p-6 space-y-3" style="display: none;">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">File Tidak Ditemukan</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400" x-text="errorMessage || 'File Google Drive dengan ID tersebut tidak ditemukan atau telah dihapus.'"></p>
                    <button type="button" @click="closeModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-800 dark:text-white transition-colors cursor-pointer">
                        Tutup Modal
                    </button>
                </div>

                <!-- 4. ACCESS DENIED STATE (PRD FR-13) -->
                <div x-show="!loading && status === 'access_denied'" class="text-center max-w-md p-6 space-y-3" style="display: none;">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">Akses Ditolak</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400" x-text="errorMessage || 'Service Account tidak memiliki izin akses ke file ini di Google Drive. Silakan bagikan akses file ke Service Account.'"></p>
                    <button type="button" @click="closeModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-800 dark:text-white transition-colors cursor-pointer">
                        Tutup Modal
                    </button>
                </div>

                <!-- 5. INVALID DOCUMENT STATE (PRD FR-13) -->
                <div x-show="!loading && status === 'invalid_document'" class="text-center max-w-md p-6 space-y-3" style="display: none;">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">Referensi Dokumen Tidak Valid</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400" x-text="errorMessage || 'Format Document Reference tidak dikenali sebagai Google Drive File ID.'"></p>
                    <button type="button" @click="closeModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-800 dark:text-white transition-colors cursor-pointer">
                        Tutup Modal
                    </button>
                </div>

                <!-- 6. GENERAL ERROR STATE (PRD FR-13) -->
                <div x-show="!loading && status === 'error'" class="text-center max-w-md p-6 space-y-3" style="display: none;">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">Gagal Memuat Dokumen</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400" x-text="errorMessage || 'Terjadi kesalahan sistem saat mencoba mengambil dokumen dari Google Drive.'"></p>
                    <div class="flex items-center justify-center gap-2 pt-2">
                        <button type="button" @click="openModal()" class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-xs font-bold text-white transition-colors cursor-pointer">
                            Coba Lagi
                        </button>
                        <button type="button" @click="closeModal()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-xs font-bold text-slate-800 dark:text-white transition-colors cursor-pointer">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function docViewer() {
        return {
            modalOpen: false,
            loading: false,
            status: 'idle',
            metadata: null,
            errorMessage: '',

            openModal() {
                this.modalOpen = true;
                this.loading = true;
                this.status = 'loading';
                this.errorMessage = '';
                this.metadata = null;

                fetch('{{ route('contracts.document.metadata', $contract['lop']) }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    this.loading = false;
                    if (data.success && data.status === 'available') {
                        this.status = 'available';
                        this.metadata = data.metadata;
                    } else {
                        this.status = data.status || 'error';
                        this.errorMessage = data.error || 'Dokumen tidak dapat diakses.';
                    }
                })
                .catch(err => {
                    this.loading = false;
                    this.status = 'error';
                    this.errorMessage = 'Terjadi kesalahan koneksi saat memverifikasi dokumen.';
                });
            },

            closeModal() {
                this.modalOpen = false;
                this.status = 'idle';
                this.metadata = null;
            }
        }
    }
</script>
@endsection
