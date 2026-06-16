@props([
    'label' => '',
    'value' => 0,
    'color' => 'indigo',
])

@php
$colors = [
    'indigo'  => 'border-indigo-200 dark:border-indigo-900/50 text-indigo-600 dark:text-indigo-400',
    'emerald' => 'border-emerald-200 dark:border-emerald-900/50 text-emerald-600 dark:text-emerald-400',
    'red'     => 'border-red-200 dark:border-red-900/50 text-red-600 dark:text-red-400',
    'slate'   => 'border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400',
    'amber'   => 'border-amber-200 dark:border-amber-900/50 text-amber-600 dark:text-amber-400',
];
$colorClass = $colors[$color] ?? $colors['indigo'];
@endphp

<div {{ $attributes->merge(['class' => "bg-white dark:bg-slate-900 rounded-xl border {$colorClass} p-4 shadow-sm"]) }}>
    <div class="flex items-start justify-between">
        <div>
            <p class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">{{ $value }}</p>
            <p class="text-xs font-medium text-gray-500 dark:text-slate-400 mt-0.5">{{ $label }}</p>
        </div>
        @if($slot->isNotEmpty())
            <div class="text-gray-300 dark:text-slate-600 mt-0.5 flex-shrink-0">{{ $slot }}</div>
        @endif
    </div>
</div>
