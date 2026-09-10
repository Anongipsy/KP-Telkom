@extends('layouts.app')

@section('title', 'Audit Trail & Activity Log')
@section('page_title')
    <span class="text-slate-900 dark:text-white">Audit Trail & Activity Log</span>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                <a href="{{ route('dashboard') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">Dashboard</a>
                <span>/</span>
                <span class="text-slate-700 dark:text-slate-200 font-semibold">Admin Panel</span>
                <span>/</span>
                <span class="text-red-600 dark:text-red-400 font-mono font-semibold">Audit Trail</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1 flex items-center gap-2.5">
                <svg class="w-6 h-6 text-red-600 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Audit Trail & Activity Log</span>
            </h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Catatan riwayat aktivitas pengguna dan sistem: <strong class="text-slate-800 dark:text-slate-300">WHO, WHAT, WHEN, TARGET</strong> (PRD FR-16).
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-mono font-bold text-slate-700 dark:text-slate-300 shadow-sm">
                Total: {{ $logs->total() }} Log
            </span>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="p-4 rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-lg">
        <form method="GET" action="{{ route('admin.audit-logs') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            <!-- Search Query -->
            <div class="sm:col-span-6 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input
                    type="text"
                    name="q"
                    value="{{ $filters['q'] ?? '' }}"
                    placeholder="Cari LOP, target reference, atau aksi..."
                    class="w-full pl-9 pr-4 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors"
                >
            </div>

            <!-- Action Filter -->
            <div class="sm:col-span-4">
                <select
                    name="action"
                    class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors"
                >
                    <option value="">Semua Aksi (WHAT)</option>
                    @foreach($availableActions as $action)
                        <option value="{{ $action }}" {{ ($filters['action'] ?? '') === $action ? 'selected' : '' }}>
                            {{ strtoupper($action) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Submit & Reset Buttons -->
            <div class="sm:col-span-2 flex items-center gap-2">
                <button
                    type="submit"
                    class="flex-1 px-4 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-white font-bold text-xs shadow-md shadow-red-600/20 transition-all text-center cursor-pointer"
                >
                    Filter
                </button>
                @if(!empty($filters['q']) || !empty($filters['action']))
                    <a
                        href="{{ route('admin.audit-logs') }}"
                        class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white transition-colors"
                        title="Reset Filter"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Audit Logs Table -->
    <div class="rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl overflow-hidden">
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-950/80 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800 uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-5 py-3.5">WHO (Pengguna)</th>
                        <th class="px-5 py-3.5">WHAT (Aksi)</th>
                        <th class="px-5 py-3.5">TARGET</th>
                        <th class="px-5 py-3.5">WHEN (Waktu)</th>
                        <th class="px-5 py-3.5 text-right">METADATA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-sans">
                    @forelse($logs as $log)
                        @php
                            $actionColor = match($log->action) {
                                'login' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/20',
                                'logout' => 'bg-slate-100 dark:bg-slate-500/10 text-slate-700 dark:text-slate-400 border-slate-200 dark:border-slate-500/20',
                                'failed_login', 'failed_login_throttled', 'failed_login_inactive' => 'bg-red-500/10 text-red-700 dark:text-red-400 border-red-500/20',
                                'contract_create' => 'bg-sky-500/10 text-sky-700 dark:text-sky-400 border-sky-500/20',
                                'contract_update' => 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/20',
                                'document_view_metadata', 'document_view_stream' => 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 border-indigo-500/20',
                                'notification_generated' => 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/20',
                                'expiration_check_completed' => 'bg-purple-500/10 text-purple-700 dark:text-purple-400 border-purple-500/20',
                                default => 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700',
                            };
                        @endphp
                        <!-- Row Item with scoped Alpine state -->
                        <tbody x-data="{ expanded: false }" class="divide-y divide-slate-100 dark:divide-slate-800/40">
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                                <!-- WHO -->
                                <td class="px-5 py-3.5">
                                    @if($log->user)
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-[10px] font-bold text-slate-700 dark:text-slate-300">
                                                {{ strtoupper(substr($log->user->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-900 dark:text-white">{{ $log->user->name }}</p>
                                                <p class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">{{ $log->user->email }}</p>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-500 italic flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                            <span>System / Guest</span>
                                        </span>
                                    @endif
                                </td>

                                <!-- WHAT -->
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold font-mono border {{ $actionColor }}">
                                        {{ strtoupper($log->action) }}
                                    </span>
                                </td>

                                <!-- TARGET -->
                                <td class="px-5 py-3.5">
                                    @if($log->target_reference)
                                        <div class="space-y-0.5">
                                            <span class="font-mono font-bold text-red-600 dark:text-red-400">{{ $log->target_reference }}</span>
                                            @if($log->target_type)
                                                <span class="text-[10px] text-slate-500 block font-mono">type: {{ $log->target_type }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-600">-</span>
                                    @endif
                                </td>

                                <!-- WHEN -->
                                <td class="px-5 py-3.5">
                                    <div>
                                        <span class="text-slate-800 dark:text-slate-200 font-mono text-[11px]">{{ $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '-' }}</span>
                                        <span class="text-[10px] text-slate-500 block">{{ $log->created_at ? $log->created_at->diffForHumans() : '' }}</span>
                                    </div>
                                </td>

                                <!-- METADATA -->
                                <td class="px-5 py-3.5 text-right">
                                    @if(!empty($log->metadata))
                                        <button
                                            type="button"
                                            @click="expanded = !expanded"
                                            class="px-2 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-[10px] font-mono text-slate-700 dark:text-slate-300 transition-colors cursor-pointer"
                                        >
                                            <span x-text="expanded ? 'Tutup Detail' : 'Lihat Data'"></span>
                                        </button>
                                    @else
                                        <span class="text-slate-400 dark:text-slate-600 text-[11px]">-</span>
                                    @endif
                                </td>
                            </tr>

                            <!-- Expandable Metadata Row -->
                            @if(!empty($log->metadata))
                                <tr x-show="expanded" x-cloak style="display: none;" class="bg-slate-50 dark:bg-slate-950/80 border-t border-slate-200 dark:border-slate-800/60">
                                    <td colspan="5" class="px-5 py-3">
                                        <div class="p-3 rounded-2xl bg-slate-900 border border-slate-800 font-mono text-[11px] text-slate-300 overflow-x-auto custom-scrollbar">
                                            <pre class="whitespace-pre-wrap text-emerald-400">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    @empty
                        <tbody>
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-slate-400 dark:text-slate-500">
                                    <svg class="w-8 h-8 mx-auto text-slate-300 dark:text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="text-xs">Belum ada riwayat audit log yang sesuai.</p>
                                </td>
                            </tr>
                        </tbody>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($logs->hasPages())
            <div class="px-5 py-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
