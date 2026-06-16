<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between" x-data x-init>
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div>
                    <h1 id="wf-name" class="text-base font-semibold text-gray-900 dark:text-white">Chargement…</h1>
                    <span id="wf-badge"></span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a id="btn-edit" href="#" class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Éditeur
                </a>
                <a id="btn-test" href="#" class="inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-slate-800 hover:bg-gray-50 text-gray-700 dark:text-slate-200 border border-gray-300 dark:border-slate-700 text-xs font-semibold rounded-lg transition">Tester</a>
                <a id="btn-logs" href="#" class="inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-slate-800 hover:bg-gray-50 text-gray-700 dark:text-slate-200 border border-gray-300 dark:border-slate-700 text-xs font-semibold rounded-lg transition">Logs</a>
            </div>
        </div>
    </x-slot>

    <div class="p-6 space-y-6" x-data="workflowShow({{ $workflowId }})" x-init="init()">

        {{-- Loading --}}
        <template x-if="!loaded">
            <div class="flex items-center justify-center py-24">
                <svg class="w-6 h-6 animate-spin text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </div>
        </template>

        <template x-if="loaded">
            <div class="space-y-6">
                {{-- Stats --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-indigo-200 dark:border-indigo-900/50 p-4 shadow-sm">
                        <p class="text-2xl font-extrabold text-gray-900 dark:text-white" x-text="wf.stats?.total_executions ?? 0"></p>
                        <p class="text-xs font-medium text-gray-500 dark:text-slate-400 mt-0.5">Exécutions</p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-emerald-200 dark:border-emerald-900/50 p-4 shadow-sm">
                        <p class="text-2xl font-extrabold text-gray-900 dark:text-white" x-text="wf.stats?.success_count ?? 0"></p>
                        <p class="text-xs font-medium text-gray-500 dark:text-slate-400 mt-0.5">Succès</p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-red-200 dark:border-red-900/50 p-4 shadow-sm">
                        <p class="text-2xl font-extrabold text-gray-900 dark:text-white" x-text="wf.stats?.error_count ?? 0"></p>
                        <p class="text-xs font-medium text-gray-500 dark:text-slate-400 mt-0.5">Erreurs</p>
                    </div>
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 p-4 shadow-sm">
                        <p class="text-2xl font-extrabold text-gray-900 dark:text-white" x-text="wf.stats?.nodes_count ?? 0"></p>
                        <p class="text-xs font-medium text-gray-500 dark:text-slate-400 mt-0.5">Nœuds</p>
                    </div>
                </div>


                {{-- Rename + Danger zone --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 p-5">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Renommer le workflow</h2>
                        <div class="flex gap-2">
                            <input type="text" x-model="newName" @keydown.enter="rename()"
                                   class="flex-1 px-3 py-2 text-sm bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-700 rounded-lg text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition" />
                            <button @click="rename()" :disabled="renaming"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-xs font-semibold rounded-lg transition"
                                    x-text="renaming ? '…' : 'Sauvegarder'"></button>
                        </div>
                        <p x-show="renameSuccess" class="text-xs text-emerald-600 mt-2">Renommé avec succès.</p>
                    </div>

                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-red-200 dark:border-red-900/40 p-5" x-data="{ confirm: false, deleting: false }">
                        <h2 class="text-sm font-semibold text-red-600 dark:text-red-400 mb-1">Zone de danger</h2>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mb-4">La suppression est irréversible.</p>
                        <div x-show="!confirm">
                            <button @click="confirm = true" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-lg transition">Supprimer ce workflow</button>
                        </div>
                        <div x-show="confirm" class="flex items-center gap-3">
                            <button @click="deleteWorkflow()" :disabled="deleting"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white text-xs font-semibold rounded-lg transition"
                                    x-text="deleting ? 'Suppression…' : 'Confirmer'"></button>
                            <button @click="confirm = false" class="text-xs text-gray-500 hover:text-gray-700 dark:text-slate-400 transition">Annuler</button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <script>
    function workflowShow(id) {
        return {
            wf: {},
            loaded: false,
            newName: '',
            renaming: false,
            renameSuccess: false,
            async init() {
                try {
                    this.wf = await api(`/api/workflows/${id}`);
                    this.newName = this.wf.name;
                    this.loaded = true;
                    document.getElementById('wf-name').textContent = this.wf.name;
                    document.getElementById('wf-badge').innerHTML = this.wf.is_active
                        ? '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>Actif</span>'
                        : '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400"><span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>Inactif</span>';
                    document.getElementById('btn-edit').href  = `/workflows/${id}/edit`;
                    document.getElementById('btn-test').href  = `/workflows/${id}/test`;
                    document.getElementById('btn-logs').href  = `/workflows/${id}/logs`;
                } catch(e) {
                    this.loaded = true;
                }
            },
            async rename() {
                if (!this.newName.trim()) return;
                this.renaming = true;
                try {
                    await api(`/api/workflows/${this.wf.id}`, { method: 'PATCH', body: JSON.stringify({ name: this.newName }) });
                    this.wf.name = this.newName;
                    document.getElementById('wf-name').textContent = this.newName;
                    this.renameSuccess = true;
                    setTimeout(() => this.renameSuccess = false, 3000);
                } finally { this.renaming = false; }
            },
            async deleteWorkflow() {
                this.$el.querySelector('[\\@click="deleteWorkflow()"]').disabled = true;
                await api(`/api/workflows/${this.wf.id}`, { method: 'DELETE' });
                window.location.href = '/dashboard';
            }
        };
    }
    </script>
</x-app-layout>
