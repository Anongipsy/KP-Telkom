@props([
    'name',
    'label',
    'options' => [],
    'value' => null,
    'required' => false,
    'hint' => null,
])

@php
    $selectedValue = old($name, $value);
@endphp

<div class="space-y-1.5">
    <label for="{{ $name }}" class="block text-xs font-bold text-slate-700 dark:text-slate-300">
        {{ $label }}
        @if($required)
            <span class="text-red-500 font-bold">*</span>
        @endif
    </label>

    <select
        name="{{ $name }}"
        id="{{ $name }}"
        @if($required) required @endif
        {{ $attributes->merge(['class' => 'w-full px-3.5 py-2.5 rounded-xl bg-slate-50 dark:bg-slate-950 border text-xs text-slate-900 dark:text-white focus:outline-none transition-colors ' . ($errors->has($name) ? 'border-red-500/80 focus:ring-2 focus:ring-red-500/50 focus:border-red-500' : 'border-slate-300 dark:border-slate-800 focus:ring-2 focus:ring-red-500/40 focus:border-red-500/40')]) }}
    >
        @foreach($options as $optValue => $optLabel)
            <option value="{{ $optValue }}" {{ (string) $selectedValue === (string) $optValue ? 'selected' : '' }}>
                {{ $optLabel }}
            </option>
        @endforeach
    </select>

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
