@props(['notification'])

@php
    $isOverdue = $notification->alert_type === 'OVERDUE';
    $days = $notification->days_remaining;
@endphp

<div
    x-data="{ isRead: {{ $notification->is_read ? 'true' : 'false' }}, loading: false }"
    x-show="!isRead"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-95"
    class="flex items-start gap-3 p-3.5 rounded-xl bg-slate-900/80 border border-slate-800 hover:border-slate-700 transition-all text-xs"
>
    <!-- Notification Type Icon -->
    <div class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center {{ $isOverdue ? 'bg-red-500/15 text-red-400 border border-red-500/25' : 'bg-amber-500/15 text-amber-400 border border-amber-500/25' }}">
        @if($isOverdue)
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        @else
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        @endif
    </div>

    <!-- Notification Details -->
    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
            <span class="font-bold text-white font-mono">{{ $notification->lop_reference }}</span>
            <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-semibold {{ $isOverdue ? 'bg-red-500/20 text-red-300' : 'bg-amber-500/20 text-amber-300' }}">
                {{ $isOverdue ? 'Overdue ' . abs($days) . ' hari' : 'H-' . $days }}
            </span>
        </div>
        <p class="text-slate-400 text-[11px] mt-0.5">
            Kontrak memerlukan tindakan perpanjangan / review administrasi.
        </p>
        <span class="text-[10px] text-slate-500 mt-1 block">
            {{ $notification->created_at ? $notification->created_at->diffForHumans() : 'Baru saja' }}
        </span>
    </div>

    <!-- Mark as Read Button -->
    <button
        type="button"
        @click="
            loading = true;
            fetch('{{ route('notifications.read', $notification->id) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    isRead = true;
                    $dispatch('notification-marked-read');
                }
            })
            .catch(err => console.error(err))
            .finally(() => loading = false);
        "
        :disabled="loading"
        class="shrink-0 p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
        title="Tandai sudah dibaca"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
    </button>
</div>
