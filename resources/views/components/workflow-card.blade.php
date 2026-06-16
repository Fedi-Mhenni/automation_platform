@props(['workflow', 'executionCount' => 0])

<div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md hover:border-indigo-200 dark:hover:border-indigo-900/60 transition-all duration-200 flex flex-col gap-4">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-3">
        <div class="flex-1 min-w-0">
            <a href="{{ route('workflows.show', $workflow) }}"
               class="text-sm font-semibold text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition truncate block">
                {{ $workflow->name }}
            </a>
            <p class="text-[11px] text-gray-400 dark:text-slate-500 mt-0.5">
                {{ $workflow->created_at?->diffForHumans() }}
            </p>
        </div>
        <x-status-badge :active="$workflow->is_active" class="flex-shrink-0 mt-0.5" />
    </div>

    {{-- Execution count --}}
    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-slate-400">
        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <span>{{ $executionCount }} exécution{{ $executionCount !== 1 ? 's' : '' }}</span>
    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-1.5 pt-1 border-t border-gray-100 dark:border-slate-800">
        <a href="{{ route('workflows.edit', $workflow) }}"
           class="flex-1 text-center py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/20 hover:bg-indigo-100 dark:hover:bg-indigo-950/40 rounded-lg transition">
            Éditeur
        </a>
        <a href="{{ route('workflows.show', $workflow) }}"
           class="flex-1 text-center py-1.5 text-xs font-medium text-gray-600 dark:text-slate-300 bg-gray-50 dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition">
            Détails
        </a>
        <a href="{{ route('workflows.logs', $workflow) }}"
           class="flex-1 text-center py-1.5 text-xs font-medium text-gray-600 dark:text-slate-300 bg-gray-50 dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition">
            Logs
        </a>
    </div>
</div>
