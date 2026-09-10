@props(['stage'])

@php
    $stageValue = is_object($stage) && enum_exists(get_class($stage)) ? $stage->value : (string) $stage;
    $stageValue = strtoupper(trim($stageValue));

    $stageConfig = match($stageValue) {
        'F0' => [
            'label' => 'F0 Lead',
            'badge' => 'bg-slate-100 dark:bg-slate-700/40 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-600/50',
            'dot' => 'bg-slate-500 dark:bg-slate-400',
        ],
        'F1' => [
            'label' => 'F1 Opportunity',
            'badge' => 'bg-sky-500/10 dark:bg-sky-500/15 text-sky-700 dark:text-sky-300 border-sky-500/30',
            'dot' => 'bg-sky-500 dark:bg-sky-400',
        ],
        'F2' => [
            'label' => 'F2 Quote',
            'badge' => 'bg-indigo-500/10 dark:bg-indigo-500/15 text-indigo-700 dark:text-indigo-300 border-indigo-500/30',
            'dot' => 'bg-indigo-500 dark:bg-indigo-400',
        ],
        'F3' => [
            'label' => 'F3 Bidding',
            'badge' => 'bg-amber-500/10 dark:bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30',
            'dot' => 'bg-amber-500 dark:bg-amber-400',
        ],
        'F4' => [
            'label' => 'F4 Negotiation',
            'badge' => 'bg-emerald-500/10 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30',
            'dot' => 'bg-emerald-500 dark:bg-emerald-400',
        ],
        default => [
            'label' => $stageValue ?: 'N/A',
            'badge' => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700',
            'dot' => 'bg-slate-400 dark:bg-slate-500',
        ],
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold tracking-wide border whitespace-nowrap ' . $stageConfig['badge']]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $stageConfig['dot'] }}"></span>
    <span>{{ $stageConfig['label'] }}</span>
</span>
