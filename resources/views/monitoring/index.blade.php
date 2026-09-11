@extends('layouts.app')

@section('title', 'Early Warning & Notifikasi')
@section('page_title')
    <span class="text-slate-900 dark:text-white">Early Warning & Notifikasi</span>
@endsection

@section('content')
<div
    class="space-y-6"
    x-data="{
        activeTab: '{{ $activeTab ?? 'expiration' }}',
        activeHealthCategory: 'overdue'
    }"
>
    <!-- Header Section with Tab Switcher -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-5 rounded-3xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl">
        <div>
            <h2 class="text-xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                <svg class="w-6 h-6 text-red-600 dark:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Early Warning & Pusat Notifikasi</span>
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Pantau risiko masa berlaku kontrak jatuh tempo dan tindak lanjuti peringatan dini tepat waktu (PRD FR-09, FR-10, FR-11).
            </p>
        </div>

        <!-- Main Tab Switcher -->
        <div class="p-1 rounded-2xl bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 flex items-center">
            <button
                type="button"
                @click="activeTab = 'expiration'"
                :class="activeTab === 'expiration' ? 'bg-red-600 text-white shadow-md font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
                class="px-3.5 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5 cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                <span>Status Kedaluwarsa</span>
            </button>
            <button
                type="button"
                @click="activeTab = 'notifications'"
                :class="activeTab === 'notifications' ? 'bg-red-600 text-white shadow-md font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200'"
                class="px-3.5 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1.5 cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                <span>Pusat Notifikasi</span>
                @if($unreadCount > 0)
                    <span class="px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-white text-red-600">
                        {{ $unreadCount }}
                    </span>
                @endif
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 1: EXPIRATION HEALTH STATUS (OVERDUE, EXPIRING SOON, ACTIVE) -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'expiration'" class="space-y-6">
        <!-- 3 Category Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Overdue (< 0 Days) -->
            <button
                type="button"
                @click="activeHealthCategory = 'overdue'"
                :class="activeHealthCategory === 'overdue' ? 'ring-2 ring-rose-500 border-rose-500/50 bg-rose-50/40 dark:bg-slate-900' : 'hover:border-slate-300 dark:hover:border-slate-700 bg-white dark:bg-slate-900/80'"
                class="p-5 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl text-left transition-all cursor-pointer"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></span>
                        <span>OVERDUE</span>
                    </span>
                    <span class="text-2xl font-extrabold font-mono text-rose-600 dark:text-rose-500">{{ count($overdueContracts) }}</span>
                </div>
                <p class="text-xs font-bold text-slate-900 dark:text-white mt-2">Lewat Jatuh Tempo</p>
                <p class="text-[10px] text-slate-500 mt-0.5">&lt; 0 hari tersisa &bull; Perlu perpanjangan / adendum</p>
            </button>

            <!-- Expiring Soon (0 - 60 Days) -->
            <button
                type="button"
                @click="activeHealthCategory = 'expiring_soon'"
                :class="activeHealthCategory === 'expiring_soon' ? 'ring-2 ring-amber-500 border-amber-500/50 bg-amber-50/40 dark:bg-slate-900' : 'hover:border-slate-300 dark:hover:border-slate-700 bg-white dark:bg-slate-900/80'"
                class="p-5 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl text-left transition-all cursor-pointer"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-amber-600 dark:text-amber-400 flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        <span>EXPIRING SOON</span>
                    </span>
                    <span class="text-2xl font-extrabold font-mono text-amber-600 dark:text-amber-400">{{ count($expiringSoonContracts) }}</span>
                </div>
                <p class="text-xs font-bold text-slate-900 dark:text-white mt-2">Mendekati Jatuh Tempo</p>
                <p class="text-[10px] text-slate-500 mt-0.5">0 – 60 hari tersisa &bull; Waspada H-60 s/d H-1</p>
            </button>

            <!-- Active (> 60 Days) -->
            <button
                type="button"
                @click="activeHealthCategory = 'active'"
                :class="activeHealthCategory === 'active' ? 'ring-2 ring-emerald-500 border-emerald-500/50 bg-emerald-50/40 dark:bg-slate-900' : 'hover:border-slate-300 dark:hover:border-slate-700 bg-white dark:bg-slate-900/80'"
                class="p-5 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl text-left transition-all cursor-pointer"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <span>ACTIVE</span>
                    </span>
                    <span class="text-2xl font-extrabold font-mono text-emerald-600 dark:text-emerald-400">{{ count($activeContracts) }}</span>
                </div>
                <p class="text-xs font-bold text-slate-900 dark:text-white mt-2">Masa Berlaku Aman</p>
                <p class="text-[10px] text-slate-500 mt-0.5">&gt; 60 hari tersisa &bull; Operasional normal</p>
            </button>
        </div>

        <!-- Category Content Tables -->
        <div class="rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800/80 bg-slate-50 dark:bg-slate-950/40 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span x-text="activeHealthCategory === 'overdue' ? '🔴 Daftar Kontrak Overdue (< 0 Hari)' : (activeHealthCategory === 'expiring_soon' ? '🟡 Daftar Kontrak Expiring Soon (0–60 Hari)' : '🟢 Daftar Kontrak Aktif Aman (> 60 Hari)')"></span>
                    </h3>
                </div>
            </div>

            <!-- Overdue List -->
            <div x-show="activeHealthCategory === 'overdue'" class="overflow-x-auto custom-scrollbar">
                @include('monitoring.partials.contract-list', ['list' => $overdueContracts, 'type' => 'overdue'])
            </div>

            <!-- Expiring Soon List -->
            <div x-show="activeHealthCategory === 'expiring_soon'" style="display: none;" class="overflow-x-auto custom-scrollbar">
                @include('monitoring.partials.contract-list', ['list' => $expiringSoonContracts, 'type' => 'expiring_soon'])
            </div>

            <!-- Active List -->
            <div x-show="activeHealthCategory === 'active'" style="display: none;" class="overflow-x-auto custom-scrollbar">
                @include('monitoring.partials.contract-list', ['list' => $activeContracts, 'type' => 'active'])
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: NOTIFICATIONS CENTER -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'notifications'" style="display: none;" class="space-y-4">
        <!-- Notification Toolbar -->
        <div class="p-4 rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 shadow-sm dark:shadow-lg flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('monitoring.index') }}?tab=notifications&status=all"
                    class="px-3 py-1.5 rounded-xl text-xs font-semibold {{ ($filterStatus ?? 'all') === 'all' ? 'bg-red-600 text-white font-bold' : 'bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800' }}"
                >
                    Semua Notifikasi ({{ $totalNotificationCount ?? 0 }})
                </a>
                <a
                    href="{{ route('monitoring.index') }}?tab=notifications&status=unread"
                    class="px-3 py-1.5 rounded-xl text-xs font-semibold {{ ($filterStatus ?? '') === 'unread' ? 'bg-red-600 text-white font-bold' : 'bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800' }}"
                >
                    Belum Dibaca ({{ $unreadCount ?? 0 }})
                </a>
                <a
                    href="{{ route('monitoring.index') }}?tab=notifications&status=read"
                    class="px-3 py-1.5 rounded-xl text-xs font-semibold {{ ($filterStatus ?? '') === 'read' ? 'bg-red-600 text-white font-bold' : 'bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800' }}"
                >
                    Sudah Dibaca ({{ $readCount ?? 0 }})
                </a>
            </div>

            <div class="flex items-center gap-2">
                @if($unreadCount > 0)
                    <form action="{{ route('notifications.read-all') }}" method="POST">
                        @csrf
                        <button
                            type="submit"
                            class="px-3.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-700 text-xs font-bold transition-colors flex items-center gap-1.5 cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <span>Tandai Semua Sudah Dibaca</span>
                        </button>
                    </form>
                @endif

                @if($notifications->count() > 0)
                    <form action="{{ route('notifications.clear-all') }}" method="POST" onsubmit="return confirm('Hapus semua notifikasi?')">
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            class="px-3.5 py-1.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-400 border border-rose-500/20 text-xs font-bold transition-colors flex items-center gap-1.5 cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5 text-rose-500 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            <span>Hapus Semua</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Notifications List -->
        <div class="space-y-3">
            @forelse($notifications as $notif)
                @php
                    $isOverdue = $notif->alert_type === 'OVERDUE';
                    $borderColor = $notif->is_read
                        ? 'border-slate-200 dark:border-slate-800/60 bg-slate-50/50 dark:bg-slate-950/40 opacity-75'
                        : ($isOverdue ? 'border-rose-300 dark:border-rose-500/40 bg-white dark:bg-slate-900/90 shadow-sm dark:shadow-lg' : 'border-amber-300 dark:border-amber-500/40 bg-white dark:bg-slate-900/90 shadow-sm dark:shadow-lg');
                @endphp
                <div class="p-4 rounded-3xl border {{ $borderColor }} transition-all flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 {{ $isOverdue ? 'bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400' : 'bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($isOverdue)
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                @endif
                            </svg>
                        </div>

                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-xs font-bold text-red-600 dark:text-red-400">{{ $notif->lop_reference }}</span>
                                <span class="px-2 py-0.2 rounded text-[10px] font-mono font-bold {{ $isOverdue ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20' : 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20' }}">
                                    {{ $notif->alert_type }} ({{ $notif->days_remaining < 0 ? abs($notif->days_remaining) . 'h lalu' : $notif->days_remaining . 'h lagi' }})
                                </span>
                                @if(!$notif->is_read)
                                    <span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-800 dark:text-slate-300 font-medium">{{ $notif->message }}</p>
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">{{ $notif->created_at->diffForHumans() }} &bull; {{ $notif->created_at->format('d M Y H:i') }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
                        @if(!$notif->is_read)
                            <form action="{{ route('notifications.read', $notif->id) }}" method="POST">
                                @csrf
                                <button
                                    type="submit"
                                    class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white text-xs font-semibold border border-slate-200 dark:border-slate-700 transition-colors cursor-pointer"
                                >
                                    Tandai Dibaca
                                </button>
                            </form>
                        @endif

                        <a
                            href="{{ route('contracts.show', $notif->lop_reference) }}"
                            class="px-3 py-1.5 rounded-xl bg-red-600/10 hover:bg-red-600 text-red-600 hover:text-white dark:text-red-400 border border-red-500/20 text-xs font-bold transition-all"
                        >
                            Buka Kontrak &rarr;
                        </a>

                        <form action="{{ route('notifications.destroy', $notif->id) }}" method="POST" onsubmit="return confirm('Hapus notifikasi ini?')">
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="p-1.5 rounded-xl bg-slate-100 hover:bg-rose-500/10 dark:bg-slate-800 dark:hover:bg-rose-500/20 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 border border-slate-200 dark:border-slate-700 transition-colors cursor-pointer"
                                title="Hapus Notifikasi"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-12 rounded-3xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 text-center text-slate-500 space-y-2">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200">Tidak ada notifikasi aktif</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">Semua kontrak terpantau aman dan tidak ada batas masa berlaku yang mendesak.</p>
                </div>
            @endforelse

            @if($notifications->hasPages())
                <div class="pt-4">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
