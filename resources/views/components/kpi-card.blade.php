@props([
    'title',
    'value',
    'subtitle' => null,
    'icon' => null,
    'accent' => 'slate',
    'trend' => null,
])

@php
    $accentClasses = match($accent) {
        'red' => [
            'border' => 'border-red-500/30 hover:border-red-500/50',
            'glow' => 'from-red-500/10 to-transparent',
            'icon_bg' => 'bg-red-500/15 text-red-400 border border-red-500/25',
            'text' => 'text-red-400',
        ],
        'emerald' => [
            'border' => 'border-emerald-500/30 hover:border-emerald-500/50',
            'glow' => 'from-emerald-500/10 to-transparent',
            'icon_bg' => 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/25',
            'text' => 'text-emerald-400',
        ],
        'amber' => [
            'border' => 'border-amber-500/30 hover:border-amber-500/50',
            'glow' => 'from-amber-500/10 to-transparent',
            'icon_bg' => 'bg-amber-500/15 text-amber-400 border border-amber-500/25',
            'text' => 'text-amber-400',
        ],
        'sky', 'blue' => [
            'border' => 'border-sky-500/30 hover:border-sky-500/50',
            'glow' => 'from-sky-500/10 to-transparent',
            'icon_bg' => 'bg-sky-500/15 text-sky-400 border border-sky-500/25',
            'text' => 'text-sky-400',
        ],
        'indigo', 'purple' => [
            'border' => 'border-indigo-500/30 hover:border-indigo-500/50',
            'glow' => 'from-indigo-500/10 to-transparent',
            'icon_bg' => 'bg-indigo-500/15 text-indigo-400 border border-indigo-500/25',
            'text' => 'text-indigo-400',
        ],
        default => [
            'border' => 'border-slate-800 hover:border-slate-700',
            'glow' => 'from-slate-800/20 to-transparent',
            'icon_bg' => 'bg-slate-800 text-slate-300 border border-slate-700',
            'text' => 'text-white',
        ],
    };
@endphp

<div class="relative overflow-hidden rounded-2xl bg-gradient-to-b from-slate-900/90 to-slate-950/90 p-5 border {{ $accentClasses['border'] }} shadow-lg shadow-black/40 transition-all duration-200 group">
    <!-- Top accent glow -->
    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r {{ $accentClasses['glow'] }}"></div>

    <div class="flex items-start justify-between gap-3">
        <div class="flex-1 min-w-0">
            <p class="text-xs font-medium uppercase tracking-wider text-slate-400 truncate">{{ $title }}</p>
            <p class="mt-2 text-2xl sm:text-3xl font-extrabold text-white tracking-tight leading-tight truncate">
                {{ $value }}
            </p>
            @if($subtitle)
                <p class="mt-1 text-xs text-slate-400 font-medium truncate">
                    {{ $subtitle }}
                </p>
            @endif
        </div>

        @if($icon)
            <div class="shrink-0 w-11 h-11 rounded-xl flex items-center justify-center {{ $accentClasses['icon_bg'] }} shadow-inner">
                {{ $icon }}
            </div>
        @endif
    </div>

    @if($trend)
        <div class="mt-3 pt-3 border-t border-slate-800/80 flex items-center gap-2 text-xs">
            {{ $trend }}
        </div>
    @endif
</div>
