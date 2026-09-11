<table class="w-full text-left text-xs">
    <thead class="bg-slate-50 dark:bg-slate-950/80 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-200 dark:border-slate-800 uppercase tracking-wider text-[10px]">
        <tr>
            <th class="px-4 py-3.5">LOP & Kontrak</th>
            <th class="px-4 py-3.5">Satker / Nama GC</th>
            <th class="px-4 py-3.5 max-w-[140px] w-36">Layanan</th>
            <th class="px-4 py-3.5">Batas Masa Berlaku</th>
            <th class="px-4 py-3.5">Sisa Waktu</th>
            <th class="px-4 py-3.5">Nilai Kontrak</th>
            <th class="px-4 py-3.5">Realisasi Billcomp</th>
            <th class="px-4 py-3.5 text-right">Aksi</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-sans">
        @forelse($list as $item)
            @php
                $isOverdue = ($item['expiration_status'] ?? '') === 'OVERDUE';
                $isExpiring = ($item['expiration_status'] ?? '') === 'EXPIRING_SOON';
                $badgeStyle = $isOverdue
                    ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/25 font-extrabold'
                    : ($isExpiring ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/25 font-bold' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/25');
            @endphp
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                <td class="px-4 py-3.5">
                    <span class="font-mono font-bold text-red-600 dark:text-red-400">{{ $item['lop'] }}</span>
                    <span class="text-[10px] text-slate-500 block font-mono">{{ $item['contract_number'] ?: '-' }}</span>
                </td>
                <td class="px-4 py-3.5">
                    <span class="font-bold text-slate-900 dark:text-white block">{{ $item['satker'] ?: '-' }}</span>
                    @if(!empty($item['nama_gc']))
                        <span class="text-[10px] text-slate-500 block">🏢 {{ $item['nama_gc'] }}</span>
                    @else
                        <span class="text-[10px] text-slate-400 block">-</span>
                    @endif
                </td>
                <td class="px-4 py-3.5 max-w-[140px]">
                    <span class="text-slate-700 dark:text-slate-300 block truncate max-w-[140px] cursor-default" title="{{ $item['service'] ?? '-' }}">
                        {{ $item['service'] ?? '-' }}
                    </span>
                </td>
                <td class="px-4 py-3.5 font-mono text-slate-700 dark:text-slate-300">
                    {{ $item['end_date'] ?: '-' }}
                </td>
                <td class="px-4 py-3.5">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-mono border {{ $badgeStyle }}">
                        @if($isOverdue)
                            Lewat {{ abs($item['days_remaining'] ?? 0) }} hari
                        @else
                            {{ $item['days_remaining'] ?? 0 }} hari tersisa
                        @endif
                    </span>
                </td>
                <td class="px-4 py-3.5 font-mono font-extrabold text-slate-900 dark:text-white">
                    {{ $item['revenue_formatted'] ?? '-' }}
                </td>
                <td class="px-4 py-3.5">
                    <div class="space-y-1 min-w-[100px]">
                        <div class="flex items-center justify-between text-[10px] font-mono">
                            <span class="text-slate-500 dark:text-slate-400">{{ $item['billcomp_status'] ?? '-' }}</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ $item['billcomp_percentage'] ?? 0 }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-gradient-to-r from-red-500 to-rose-400 h-full rounded-full" style="width: {{ min(100, $item['billcomp_percentage'] ?? 0) }}%"></div>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-1.5">
                        <a
                            href="{{ route('contracts.show', $item['lop']) }}"
                            class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white text-[11px] font-bold transition-colors inline-block"
                        >
                            Detail
                        </a>
                        <a
                            href="{{ route('contracts.edit', $item['lop']) }}"
                            class="p-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 transition-colors inline-block"
                            title="Edit Kontrak"
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
                <td colspan="8" class="px-4 py-8 text-center text-slate-400 dark:text-slate-500">
                    Tidak ada kontrak dalam kategori ini.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
