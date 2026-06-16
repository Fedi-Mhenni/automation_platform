@props(['log'])

<tr class="hover:bg-gray-50 dark:hover:bg-slate-800/30 transition">
    <td class="px-4 py-2.5 text-xs font-mono text-gray-500 dark:text-slate-400 max-w-[120px] truncate" title="{{ $log->node_id }}">
        {{ Str::limit($log->node_id, 12) }}
    </td>
    <td class="px-4 py-2.5 text-xs font-mono text-gray-600 dark:text-slate-300">{{ $log->action }}</td>
    <td class="px-4 py-2.5">
        @if($log->status)
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400">Succès</span>
        @else
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-400">Erreur</span>
        @endif
    </td>
    <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-slate-400 max-w-xs truncate" title="{{ $log->message }}">
        {{ $log->message ?? '—' }}
    </td>
    <td class="px-4 py-2.5 text-[10px] text-gray-400 dark:text-slate-500 whitespace-nowrap">
        {{ $log->created_at?->format('d/m H:i:s') }}
    </td>
</tr>
