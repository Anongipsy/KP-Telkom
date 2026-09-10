@props(['contract'])

@php
    $lop = $contract['lop'] ?? 'N/A';
    $customer = $contract['customer'] ?? '-';
    $satker = $contract['satker'] ?? '-';
    $service = $contract['service'] ?? '-';
    $revenue = $contract['revenue_formatted'] ?? 'Rp 0';
    $realized = $contract['realized_revenue_formatted'] ?? 'Rp 0';
    $billcompPct = (int) ($contract['billcomp_percentage'] ?? 0);
    $expStatus = $contract['expiration_status'] ?? 'UNKNOWN';
    $daysRemaining = $contract['days_remaining'] ?? null;
    $invoiceStatus = $contract['invoice_status'] ?? 'UNBILLED';
    $spPo = $contract['sp_po'] ?? 'MISSING';
    $endDate = $contract['end_date'] ?? null;
@endphp

<div class="rounded-xl bg-slate-900/90 border border-slate-800 hover:border-slate-700 hover:bg-slate-900 p-4 shadow-md transition-all duration-200 group flex flex-col justify-between gap-3">
    <!-- Card Header: LOP & Expiration Status -->
    <div class="flex items-start justify-between gap-2">
        <span class="px-2 py-0.5 rounded-md font-mono text-[11px] font-bold bg-slate-800 border border-slate-700 text-red-400 group-hover:border-red-500/40 transition-colors">
            {{ $lop }}
        </span>

        <x-status-badge type="expiration" :value="$expStatus" :days="$daysRemaining" />
    </div>

    <!-- Customer & Project Info -->
    <div>
        <h4 class="text-sm font-bold text-white tracking-tight group-hover:text-red-400 transition-colors line-clamp-1" title="{{ $customer }}">
            {{ $customer }}
        </h4>
        <div class="flex items-center gap-1.5 mt-1 text-[11px] text-slate-400">
            <span class="truncate max-w-[120px]" title="{{ $satker }}">{{ $satker }}</span>
            <span>&bull;</span>
            <span class="text-slate-300 font-medium truncate max-w-[120px]" title="{{ $service }}">{{ $service }}</span>
        </div>
    </div>

    <!-- Revenue & Dates -->
    <div class="pt-2 border-t border-slate-800/80 grid grid-cols-2 gap-2 text-xs">
        <div>
            <span class="text-[10px] text-slate-500 uppercase tracking-wider block">Nilai Kontrak</span>
            <span class="font-bold text-white font-mono text-[12px] truncate block">{{ $revenue }}</span>
        </div>
        <div class="text-right">
            <span class="text-[10px] text-slate-500 uppercase tracking-wider block">End Date</span>
            <span class="font-medium text-slate-300 text-[11px] truncate block">{{ $endDate ?: '-' }}</span>
        </div>
    </div>

    <!-- Billcomp Progress Bar -->
    <div class="space-y-1">
        <div class="flex items-center justify-between text-[10px] text-slate-400">
            <span>Billcomp: <strong class="text-slate-200 font-semibold">{{ $billcompPct }}%</strong></span>
            <span class="text-slate-500 truncate">{{ $realized }}</span>
        </div>
        <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
            <div
                class="h-1.5 rounded-full transition-all duration-300 {{ $billcompPct >= 100 ? 'bg-emerald-500' : ($billcompPct > 0 ? 'bg-sky-500' : 'bg-slate-700') }}"
                style="width: {{ min(100, $billcompPct) }}%"
            ></div>
        </div>
    </div>

    <!-- Card Footer: Status Badges -->
    <div class="flex items-center justify-between pt-1 text-[11px]">
        <div class="flex items-center gap-1.5">
            <x-status-badge type="invoice" :value="$invoiceStatus" />
            <x-status-badge type="sp_po" :value="$spPo" />
        </div>

        @if(!empty($contract['contract_number']))
            <span class="text-[10px] text-slate-500 font-mono truncate max-w-[90px]" title="{{ $contract['contract_number'] }}">
                {{ $contract['contract_number'] }}
            </span>
        @endif
    </div>
</div>
