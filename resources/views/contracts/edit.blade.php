@extends('layouts.app')

@section('title', 'Edit Kontrak ' . ($contract['lop'] ?? ''))
@section('page_title')
    <span class="text-slate-900 dark:text-white">Edit Kontrak &bull; <span class="font-mono text-red-600 dark:text-red-400">{{ $contract['lop'] }}</span></span>
@endsection

@section('content')
<div class="space-y-6 max-w-4xl mx-auto" x-data="{ submitting: false }">
    <!-- Breadcrumbs -->
    <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
        <a href="{{ route('contracts.index') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            <span>Kontrak & Pipeline</span>
        </a>
        <span>/</span>
        <a href="{{ route('contracts.show', $contract['lop']) }}" class="hover:text-slate-900 dark:hover:text-white transition-colors font-mono font-bold text-red-600 dark:text-red-400">
            {{ $contract['lop'] }}
        </a>
        <span>/</span>
        <span class="text-slate-900 dark:text-slate-200 font-semibold">Edit Kontrak</span>
    </div>

    <!-- Header Card -->
    <div class="rounded-3xl bg-white dark:bg-gradient-to-r dark:from-slate-900 dark:via-slate-900 dark:to-slate-800 border border-slate-200 dark:border-slate-800 p-6 sm:p-8 shadow-sm dark:shadow-xl">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2.5 py-0.5 rounded-md font-mono text-xs font-bold bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-red-600 dark:text-red-400">
                        {{ $contract['lop'] }}
                    </span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">Baris {{ $contract['_row_index'] ?? '-' }} di Google Sheets</span>
                </div>
                <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Edit Kontrak: {{ $contract['customer'] }}
                </h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
                    Perubahan akan diperbarui langsung pada baris data terkait di Google Spreadsheet.
                </p>
            </div>
        </div>
    </div>

    <!-- Form Container -->
    <form
        action="{{ route('contracts.update', $contract['lop']) }}"
        method="POST"
        enctype="multipart/form-data"
        @submit="submitting = true"
        class="space-y-6"
    >
        @csrf
        @method('PUT')

        <!-- 1. Informasi Dasar Proyek -->
        <div class="p-6 rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-lg space-y-4">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
                <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Informasi Dasar Proyek</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-input
                    name="lop"
                    label="Nomor LOP (Identifier Tetap)"
                    :value="$contract['lop']"
                    readonly
                    hint="Nomor LOP tidak dapat diubah karena merupakan primary identifier di Google Sheets"
                />

                <x-form-input
                    name="id_mytens"
                    label="ID MyTens"
                    :value="$contract['id_mytens'] ?? ''"
                    placeholder="Contoh: TENS-2026-99"
                />

                <x-form-input
                    name="tahun"
                    label="Tahun Kontrak"
                    :value="$contract['tahun'] ?? ''"
                    placeholder="Contoh: 2026"
                />

                <x-form-input
                    name="contract_number"
                    label="Nomor Kontrak"
                    :value="$contract['contract_number']"
                    placeholder="Contoh: K.TEL.01/HK.810/2026"
                />

                <x-form-input
                    name="nama_gc"
                    label="Nama GC (Group Company)"
                    :value="$contract['nama_gc'] ?? ''"
                    placeholder="Contoh: PT Semen Indonesia Group"
                />

                <x-form-input
                    name="satker"
                    label="Satker / Divisi"
                    :value="$contract['satker'] ?? ''"
                    placeholder="Contoh: Satker Gresik"
                />

                <div class="sm:col-span-2">
                    <x-form-input
                        name="judul_proyek"
                        label="Judul Proyek"
                        :value="!empty($contract['judul_proyek']) ? $contract['judul_proyek'] : ($contract['customer'] ?? '')"
                        placeholder="Contoh: Pengadaan Jaringan Astinet & SD-WAN"
                        required
                    />
                </div>

                <div class="sm:col-span-2">
                    <x-form-input
                        type="textarea"
                        rows="3"
                        maxlength="700"
                        name="service"
                        label="Deskripsi Layanan"
                        :value="$contract['service']"
                        placeholder="Contoh: Astinet Dedicated 500 Mbps, Indibiz, Cloud"
                        hint="Maksimal 700 karakter"
                        required
                    />
                </div>
            </div>
        </div>

        <!-- 2. Finansial & Tahapan Pipeline -->
        <div
            class="p-6 rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-lg space-y-4"
            x-data="{
                revenue: '{{ $contract['revenue'] ?? 0 }}',
                billcompNominal: '{{ $contract['realized_revenue'] ?? (int) round((($contract['revenue'] ?? 0) * ($contract['billcomp_percentage'] ?? 0)) / 100) }}',
                billcompPercentage: {{ (int) ($contract['billcomp_percentage'] ?? 0) }},
                billcompStatus: '{{ $contract['billcomp_status'] ?? 'NOT_COMPLETE' }}',

                parseNum(val) {
                    if (!val) return 0;
                    return parseFloat(val.toString().replace(/[^0-9]/g, '')) || 0;
                },

                formatRupiah(val) {
                    const num = this.parseNum(val);
                    return 'Rp ' + num.toLocaleString('id-ID');
                },

                calculatePercentage() {
                    const rev = this.parseNum(this.revenue);
                    const nom = this.parseNum(this.billcompNominal);
                    if (rev > 0) {
                        this.billcompPercentage = Math.min(100, Math.max(0, Math.round((nom / rev) * 100)));
                    } else {
                        this.billcompPercentage = 0;
                    }
                    this.autoUpdateStatus();
                },

                autoUpdateStatus() {
                    if (this.billcompPercentage >= 100) {
                        this.billcompStatus = 'COMPLETED';
                    } else if (this.billcompPercentage > 0) {
                        this.billcompStatus = 'PARTIAL';
                    } else {
                        this.billcompStatus = 'NOT_COMPLETE';
                    }
                }
            }"
        >
            <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Finansial & Tahapan Pipeline</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-select
                    name="stage"
                    label="Tahapan Pipeline"
                    :options="[
                        'F0' => 'F0 Lead',
                        'F1' => 'F1 Opportunity',
                        'F2' => 'F2 Quote',
                        'F3' => 'F3 Bidding',
                        'F4' => 'F4 Negotiation',
                    ]"
                    :value="$contract['stage']"
                    required
                />

                <x-form-select
                    name="status_kontrak"
                    label="Status Kontrak"
                    :options="[
                        'BERJALAN' => 'Kontrak Berjalan',
                        'SELESAI' => 'Kontrak Selesai',
                    ]"
                    :value="$contract['status_kontrak'] ?? 'BERJALAN'"
                    hint="AM dapat mengubah status kontrak antara Kontrak Berjalan atau Kontrak Selesai"
                />

                <x-form-input
                    name="revenue"
                    label="Nilai Realisasi Win (Revenue)"
                    :value="$contract['revenue']"
                    placeholder="Contoh: 150000000 atau Rp 150.000.000"
                    required
                    x-model="revenue"
                    @input="calculatePercentage()"
                />

                <x-form-input
                    name="estimasi_nilai_proyek"
                    label="Estimasi Nilai Proyek (Opsional)"
                    :value="$contract['estimasi_nilai_proyek'] ?? ''"
                    placeholder="Contoh: 200000000"
                />

                <x-form-input
                    name="nilai_bc"
                    label="Nilai BC (Opsional)"
                    :value="$contract['nilai_bc'] ?? ''"
                    placeholder="Contoh: 12500000"
                />

                <x-form-select
                    name="invoice_status"
                    label="Status Invoice"
                    :options="[
                        'UNBILLED' => 'Unbilled (Belum Ditagihkan)',
                        'ISSUED' => 'Issued (Faktur Terbit)',
                        'PAID' => 'Paid (Sudah Terbayar)',
                    ]"
                    :value="$contract['invoice_status']"
                />

                <x-form-select
                    name="billcomp_status"
                    label="Status Billcomp"
                    :options="[
                        'NOT_COMPLETE' => 'Not Complete',
                        'PARTIAL' => 'Partial',
                        'COMPLETED' => 'Completed',
                    ]"
                    :value="$contract['billcomp_status']"
                    x-model="billcompStatus"
                />

                <!-- Nominal Realisasi Billcomp (Input oleh user) -->
                <div>
                    <label for="billcomp_nominal" class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5">
                        Nominal Realisasi Billcomp (Rp)
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            name="billcomp_nominal"
                            id="billcomp_nominal"
                            x-model="billcompNominal"
                            @input="calculatePercentage()"
                            placeholder="Contoh: 50000000"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-xs font-mono text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-red-500/40 focus:border-red-500/40 transition-colors"
                        />
                    </div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                        Terformat: <strong class="font-mono text-slate-700 dark:text-slate-300" x-text="formatRupiah(billcompNominal)"></strong>
                    </p>
                </div>

                <!-- Persentase Billcomp (Otomatis terhitung dari nominal) -->
                <div class="sm:col-span-2 p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200/80 dark:border-slate-800 space-y-2">
                    <div class="flex items-center justify-between">
                        <div>
                            <label for="billcomp_percentage" class="block text-xs font-bold text-slate-800 dark:text-slate-200">
                                Persentase Billcomp Terhitung
                            </label>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                Otomatis dihitung: (Nominal Realisasi Billcomp &divide; Nilai Win) &times; 100%
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span
                                class="px-3 py-1 rounded-xl text-xs font-mono font-extrabold border shadow-xs"
                                :class="billcompPercentage >= 100 ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' : (billcompPercentage > 0 ? 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/20' : 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20')"
                                x-text="billcompPercentage + '%'"
                            ></span>
                        </div>
                    </div>

                    <!-- Hidden input submitted with the form -->
                    <input
                        type="hidden"
                        name="billcomp_percentage"
                        id="billcomp_percentage"
                        :value="billcompPercentage"
                    />

                    <!-- Animated Realization Progress Bar -->
                    <div class="w-full bg-slate-200/70 dark:bg-slate-900 rounded-full h-2 overflow-hidden border border-slate-200 dark:border-slate-800">
                        <div
                            class="h-2 rounded-full transition-all duration-300"
                            :class="billcompPercentage >= 100 ? 'bg-emerald-500' : 'bg-sky-500'"
                            :style="'width: ' + billcompPercentage + '%'"
                        ></div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 pt-0.5">
                        <span>Realisasi: <strong class="font-mono text-slate-800 dark:text-slate-200" x-text="formatRupiah(billcompNominal)"></strong></span>
                        <span>Total Nilai Win: <strong class="font-mono text-slate-800 dark:text-slate-200" x-text="formatRupiah(revenue)"></strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Timeline & Administrasi -->
        <div class="p-6 rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-lg space-y-4">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3">
                <svg class="w-4 h-4 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span>Timeline & Administrasi Dokumen</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-input
                    name="start_date"
                    label="Tanggal Mulai (Start Date)"
                    type="date"
                    :value="$contract['start_date']"
                />

                <x-form-input
                    name="end_date"
                    label="Tanggal Berakhir (End Date)"
                    type="date"
                    :value="$contract['end_date']"
                    hint="Digunakan untuk kalkulasi Early Warning H-60"
                />

                <x-form-input
                    name="durasi_bulan"
                    label="Estimasi Durasi Pemakaian (Bulan)"
                    type="number"
                    min="1"
                    :value="$contract['durasi_bulan'] ?? ''"
                    placeholder="Contoh: 12"
                />

                <x-form-input
                    name="estimasi_bulan_bc"
                    label="Estimasi Bulan BC"
                    :value="$contract['estimasi_bulan_bc'] ?? ''"
                    placeholder="Contoh: September 2026"
                />

                <x-form-select
                    name="sp_po"
                    label="Ketersediaan SP / PO"
                    :options="[
                        'AVAILABLE' => 'AVAILABLE (Ada)',
                        'MISSING' => 'MISSING (Tidak Ada)',
                    ]"
                    :value="$contract['sp_po']"
                />

                <!-- Dokumen Kontrak (Local Storage) -->
                <div class="sm:col-span-2 space-y-2" x-data="{ fileName: '', fileSize: '', isDragging: false }">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Dokumen Kontrak (PDF / Gambar)
                    </label>

                    @if(!empty($document))
                        <!-- Current Uploaded Document Card -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="p-2.5 rounded-xl bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </span>
                                <div class="truncate">
                                    <div class="flex items-center gap-2">
                                        <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ $document->original_name }}</p>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">Tersimpan</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                        <span>{{ $document->sizeFormatted() }}</span>
                                        <span>&bull;</span>
                                        <span>Diunggah {{ $document->created_at?->translatedFormat('d M Y H:i') ?? '-' }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
                                <a
                                    href="{{ route('contracts.document.proxy', $contract['lop']) }}"
                                    target="_blank"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-200/80 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white text-xs font-semibold transition-colors"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                    <span>Lihat File</span>
                                </a>
                            </div>
                        </div>
                    @elseif(!empty($contract['document_reference']))
                        <!-- Legacy Google Drive Reference Notice -->
                        <div class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-700 dark:text-amber-400 text-xs flex items-center gap-2.5">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <div class="flex-1 truncate">
                                <span class="font-bold">Referensi Google Drive Lama:</span>
                                <span class="font-mono text-[11px] ml-1">{{ $contract['document_reference'] }}</span>
                            </div>
                        </div>
                    @endif

                    <!-- Upload New File Dropzone -->
                    <div 
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="isDragging = false; if ($event.dataTransfer.files.length) { $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change')); }"
                        :class="isDragging ? 'border-red-500 bg-red-50/50 dark:bg-red-950/20' : 'border-slate-300 dark:border-slate-700 hover:border-red-400 dark:hover:border-red-500/50'"
                        class="relative border-2 border-dashed rounded-2xl p-4 transition-all duration-200 bg-slate-50/50 dark:bg-slate-950/50 text-center"
                    >
                        <input
                            type="file"
                            id="document_file"
                            name="document_file"
                            x-ref="fileInput"
                            accept=".pdf,.png,.jpg,.jpeg,.webp"
                            @change="
                                if ($event.target.files.length > 0) {
                                    const f = $event.target.files[0];
                                    fileName = f.name;
                                    fileSize = (f.size / (1024 * 1024)).toFixed(2) + ' MB';
                                } else {
                                    fileName = '';
                                    fileSize = '';
                                }
                            "
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                        />

                        <template x-if="!fileName">
                            <div class="space-y-1 py-1">
                                <div class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-red-500/10 text-red-600 dark:text-red-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                </div>
                                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    @if(!empty($document))
                                        <span class="text-red-600 dark:text-red-400 underline">Klik untuk memilih file baru</span> (akan mengganti dokumen saat ini)
                                    @else
                                        <span class="text-red-600 dark:text-red-400 underline">Klik untuk memilih file</span> atau drag and drop
                                    @endif
                                </p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">
                                    PDF, PNG, JPG, atau WebP (Maks. 10MB) &bull;
                                </p>
                            </div>
                        </template>

                        <template x-if="fileName">
                            <div class="flex items-center justify-between p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-left">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="p-2 rounded-lg bg-red-500/10 text-red-600 dark:text-red-400 shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <div class="truncate">
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-red-500/10 text-red-600 dark:text-red-400">File Baru</span>
                                            <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate" x-text="fileName"></p>
                                        </div>
                                        <p class="text-[11px] text-slate-400 mt-0.5" x-text="fileSize"></p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click.stop.prevent="fileName = ''; fileSize = ''; $refs.fileInput.value = ''"
                                    class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-red-600 transition-colors z-20 cursor-pointer"
                                    title="Batalkan file baru"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>

                    @error('document_file')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Submit & Actions Button Bar -->
        <div class="flex items-center justify-between gap-4 p-4 rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
            <a
                href="{{ route('contracts.show', $contract['lop']) }}"
                class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white text-xs font-semibold transition-colors"
            >
                Batal
            </a>

            <button
                type="submit"
                :disabled="submitting"
                class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 disabled:opacity-50 text-white text-xs font-bold shadow-md shadow-red-600/30 transition-all cursor-pointer"
            >
                <svg x-show="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" style="display: none;">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="submitting ? 'Memperbarui di Google Sheets...' : 'Simpan Perubahan'">Simpan Perubahan</span>
            </button>
        </div>
    </form>
</div>
@endsection
