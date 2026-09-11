@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'readonly' => false,
    'placeholder' => null,
    'hint' => null,
    'rows' => 3,
    'maxlength' => null,
])

@php
    $inputValue = old($name, $value);
@endphp

<div class="space-y-1.5">
    <label for="{{ $name }}" class="block text-xs font-bold text-slate-700 dark:text-slate-300">
        {{ $label }}
        @if($required)
            <span class="text-red-500 font-bold">*</span>
        @endif
    </label>

    @if($type === 'textarea')
        <textarea
            name="{{ $name }}"
            id="{{ $name }}"
            rows="{{ $rows }}"
            @if($maxlength) maxlength="{{ $maxlength }}" @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($required) required @endif
            @if($readonly) readonly @endif
            {{ $attributes->merge(['class' => 'w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none transition-colors ' . ($errors->has($name) ? 'border-red-500/80 focus:ring-2 focus:ring-red-500/50 focus:border-red-500' : 'border-slate-300 dark:border-slate-800 focus:ring-2 focus:ring-red-500/40 focus:border-red-500/40') . ($readonly ? ' bg-slate-100 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 cursor-not-allowed border-slate-200 dark:border-slate-800/80' : '')]) }}
        >{{ $inputValue }}</textarea>
    @else
        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $name }}"
            value="{{ $inputValue }}"
            @if($maxlength) maxlength="{{ $maxlength }}" @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($required) required @endif
            @if($readonly) readonly @endif
            {{ $attributes->merge(['class' => 'w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border text-xs text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none transition-colors ' . ($errors->has($name) ? 'border-red-500/80 focus:ring-2 focus:ring-red-500/50 focus:border-red-500' : 'border-slate-300 dark:border-slate-800 focus:ring-2 focus:ring-red-500/40 focus:border-red-500/40') . ($readonly ? ' bg-slate-100 dark:bg-slate-900/60 text-slate-500 dark:text-slate-400 cursor-not-allowed border-slate-200 dark:border-slate-800/80' : '')]) }}
        />
    @endif

    @if($hint)
        <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="text-[11px] text-red-600 dark:text-red-400 flex items-center gap-1 font-medium mt-1">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>{{ $message }}</span>
        </p>
    @enderror
</div>
