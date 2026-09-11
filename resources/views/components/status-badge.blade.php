@props([
    'type' => 'expiration',
    'value' => null,
    'days' => null,
])

@php
    $val = strtoupper(trim((string) $value));
    $classes = '';
    $label = $val;
    $icon = null;

    if ($type === 'expiration') {
        if ($val === 'ACTIVE' || $val === 'ACTIVE_CONTRACT') {
            $classes = 'bg-emerald-500/10 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30';
            $label = $days !== null ? "Aktif ({$days}h)" : 'Aktif';
        } elseif ($val === 'EXPIRING_SOON') {
            $classes = 'bg-amber-500/10 dark:bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30';
            $label = $days !== null ? "H-{$days}" : 'Expiring Soon';
        } elseif ($val === 'OVERDUE') {
            $classes = 'bg-red-500/10 dark:bg-red-500/15 text-red-700 dark:text-red-300 border-red-500/30';
            $label = $days !== null ? "Overdue (" . abs($days) . "h)" : 'Overdue';
        } else {
            $classes = 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-400 border-slate-200 dark:border-slate-700';
            $label = $val ?: 'Unknown';
        }
    } elseif ($type === 'invoice') {
        if ($val === 'PAID') {
            $classes = 'bg-emerald-500/10 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30';
            $label = 'Paid';
        } elseif ($val === 'ISSUED') {
            $classes = 'bg-amber-500/10 dark:bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30';
            $label = 'Issued';
        } else {
            $classes = 'bg-slate-100 dark:bg-slate-700/40 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-600/50';
            $label = 'Unbilled';
        }
    } elseif ($type === 'billcomp') {
        if ($val === 'COMPLETED') {
            $classes = 'bg-emerald-500/10 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30';
            $label = 'Completed';
        } elseif ($val === 'PARTIAL') {
            $classes = 'bg-sky-500/10 dark:bg-sky-500/15 text-sky-700 dark:text-sky-300 border-sky-500/30';
            $label = 'Partial';
        } else {
            $classes = 'bg-slate-100 dark:bg-slate-700/40 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-600/50';
            $label = 'Not Complete';
        }
    } elseif ($type === 'sp_po') {
        if ($val === 'AVAILABLE') {
            $classes = 'bg-emerald-500/10 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30';
            $label = 'SP/PO Ada';
        } else {
            $classes = 'bg-rose-500/10 dark:bg-rose-500/15 text-rose-700 dark:text-rose-300 border-rose-500/30';
            $label = 'SP/PO Tidak Ada';
        }
    } elseif ($type === 'contract_status' || $type === 'status_kontrak') {
        if ($val === 'SELESAI' || $val === 'KONTRAK SELESAI') {
            $classes = 'bg-rose-500/10 dark:bg-rose-500/15 text-rose-700 dark:text-rose-300 border-rose-500/30';
            $label = 'Kontrak Selesai';
        } else {
            $classes = 'bg-emerald-500/10 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30';
            $label = 'Kontrak Berjalan';
        }
    }
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-semibold border ' . $classes]) }}>
    <span>{{ $label }}</span>
</span>
