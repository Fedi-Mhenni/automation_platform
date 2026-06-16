<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-base font-semibold text-gray-900 dark:text-white">Mes Workflows</h1>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5" x-text="loaded ? `${workflows.length} workflow${workflows.length !== 1 ? 's' : ''} · ${workflows.filter(w => w.is_active).length} actif${workflows.filter(w=>w.is_active).length !== 1 ? 's' : ''}` : '…'"></p>
            </div>
            <a href="{{ route('workflows.create') }}"
               class="inline-flex items-center gap-2 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Nouveau workflow
            </a>
        </div>
    </x-slot>

    <div class="p-6" x-data="dashboard()" x-init="init()">

        {{-- Stat cards --}}
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-indigo-200 dark:border-indigo-900/50 p-4 shadow-sm">
                <p class="text-2xl font-extrabold text-gray-900 dark:text-white" x-text="loaded ? workflows.length : '…'"></p>
                <p class="text-xs font-medium text-gray-500 dark:text-slate-400 mt-0.5">Total workflows</p>
            </div>
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-emerald-200 dark:border-emerald-900/50 p-4 shadow-sm">
                <p class="text-2xl font-extrabold text-gray-900 dark:text-white" x-text="loaded ? workflows.filter(w => w.is_active).length : '…'"></p>
                <p class="text-xs font-medium text-gray-500 dark:text-slate-400 mt-0.5">Workflows actifs</p>
            </div>
        </div>

        {{-- Loading --}}
        <template x-if="!loaded && !error">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <template x-for="i in 3">
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 p-5 animate-pulse h-36"></div>
                </template>
            </div>
        </template>

        {{-- Error --}}
        <template x-if="error">
            <div class="bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900/50 rounded-xl p-4 text-sm text-red-700 dark:text-red-400" x-text="error"></div>
        </template>

        {{-- Empty --}}
        <template x-if="loaded && workflows.length === 0">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 flex flex-col items-center justify-center py-20 text-center">
                <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-950/40 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Aucun workflow</h3>
                <p class="text-xs text-gray-500 dark:text-slate-400 mb-5 max-w-xs">Créez votre premier workflow pour automatiser des actions déclenchées par des événements.</p>
                <a href="{{ route('workflows.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    Créer mon premier workflow
                </a>
            </div>
        </template>

        {{-- Workflow cards --}}
        <template x-if="loaded && workflows.length > 0">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <template x-for="w in workflows" :key="w.id">
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md hover:border-indigo-200 dark:hover:border-indigo-900/60 transition-all flex flex-col gap-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <a :href="`/workflows/${w.id}`" class="text-sm font-semibold text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition truncate block" x-text="w.name"></a>
                                <p class="text-[11px] text-gray-400 dark:text-slate-500 mt-0.5" x-text="w.nodes_count + ' nœud' + (w.nodes_count !== 1 ? 's' : '')"></p>
                            </div>
                            <span :class="w.is_active ? 'bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400'"
                                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold flex-shrink-0 mt-0.5">
                                <span :class="w.is_active ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400'" class="w-1.5 h-1.5 rounded-full"></span>
                                <span x-text="w.is_active ? 'Actif' : 'Inactif'"></span>
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5 pt-1 border-t border-gray-100 dark:border-slate-800">
                            <a :href="`/workflows/${w.id}/edit`" class="flex-1 text-center py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/20 hover:bg-indigo-100 dark:hover:bg-indigo-950/40 rounded-lg transition">Éditeur</a>
                            <a :href="`/workflows/${w.id}`"       class="flex-1 text-center py-1.5 text-xs font-medium text-gray-600 dark:text-slate-300 bg-gray-50 dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition">Détails</a>
                            <a :href="`/workflows/${w.id}/logs`"  class="flex-1 text-center py-1.5 text-xs font-medium text-gray-600 dark:text-slate-300 bg-gray-50 dark:bg-slate-800 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition">Logs</a>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <script>
    function dashboard() {
        return {
            workflows: [],
            loaded: false,
            error: null,
            async init() {
                try {
                    this.workflows = await api('/api/workflows');
                    this.loaded = true;
                } catch (e) {
                    this.error = e.message ?? 'Erreur de chargement';
                    this.loaded = true;
                }
            }
        };
    }
    </script>
</x-app-layout>
