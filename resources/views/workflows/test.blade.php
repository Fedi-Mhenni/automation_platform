<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="/workflows/{{ $workflowId }}" class="text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-base font-semibold text-gray-900 dark:text-white">Test manuel · <span id="wf-name">…</span></h1>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Exécutez le workflow avec un payload personnalisé</p>
            </div>
        </div>
    </x-slot>

    <div class="p-6 max-w-4xl"
         x-data="{
             payload: JSON.stringify({ form_data: { email: 'test@example.com', nom: 'Test User', message: 'Hello' } }, null, 2),
             loading: false,
             result: null,
             error: null,
             inactive: false,
             async run() {
                 this.loading = true; this.result = null; this.error = null;
                 try {
                     let parsed;
                     try { parsed = JSON.parse(this.payload); } catch(e) { throw new Error('JSON invalide'); }
                     this.result = await api('/api/workflows/{{ $workflowId }}/test', {
                         method: 'POST',
                         body: JSON.stringify(parsed)
                     });
                 } catch(e) {
                     this.error = e.message ?? 'Erreur inconnue';
                 } finally { this.loading = false; }
             }
         }"
         x-init="api('/api/workflows/{{ $workflowId }}').then(w => { document.getElementById('wf-name').textContent = w.name; $data.inactive = !w.is_active; })">

        <template x-if="inactive">
            <div class="mb-4 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/50 rounded-lg px-4 py-2.5">
                <p class="text-xs text-amber-700 dark:text-amber-400"><strong>Workflow inactif</strong> — Le test s'exécutera quand même, mais le webhook public refusera les appels.</p>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 p-5">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Payload JSON</h2>
                <p class="text-xs text-gray-500 dark:text-slate-400 mb-3">Variables accessibles via <code class="font-mono bg-gray-100 dark:bg-slate-800 px-1 rounded">&#123;&#123;variable&#125;&#125;</code> dans les nœuds.</p>
                <textarea x-model="payload" rows="12" spellcheck="false" aria-label="Payload JSON"
                    class="w-full px-3 py-2.5 text-xs font-mono bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-lg text-gray-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                <button @click="run()" :disabled="loading"
                        class="mt-3 w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg transition">
                    <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span x-text="loading ? 'Exécution…' : 'Lancer le test'"></span>
                </button>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 p-5">
                <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Résultat</h2>

                <template x-if="!result && !error && !loading">
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <svg class="w-8 h-8 text-gray-300 dark:text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                        <p class="text-xs text-gray-400 dark:text-slate-500">Lancez un test pour voir le résultat.</p>
                    </div>
                </template>

                <template x-if="loading">
                    <div class="flex items-center justify-center py-12">
                        <span class="text-sm font-medium text-indigo-500">Exécution en cours…</span>
                    </div>
                </template>

                <template x-if="error">
                    <div class="bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800/50 rounded-lg p-3">
                        <p class="text-xs text-red-700 dark:text-red-400 font-medium" x-text="error"></p>
                    </div>
                </template>

                <template x-if="result">
                    <div class="space-y-3">
                        <div :class="result.success ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200' : 'bg-red-50 dark:bg-red-950/30 border-red-200'"
                             class="border rounded-lg px-3 py-2.5 flex items-center gap-2">
                            <span class="text-xs font-semibold" :class="result.success ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400'"
                                  x-text="result.success ? '✓ Exécution réussie' : '✗ Exécution échouée'"></span>
                        </div>
                        <template x-if="result.result?.error">
                            <div class="bg-red-50 dark:bg-red-950/30 border border-red-200 rounded-lg px-3 py-2">
                                <p class="text-xs font-mono text-red-600 dark:text-red-400" x-text="result.result.error"></p>
                            </div>
                        </template>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-2">Chemin d'exécution</p>
                            <div class="space-y-1">
                                <template x-for="[nodeId, status] in Object.entries(result.result?.path ?? {})">
                                    <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg"
                                         :class="status === 'executed' ? 'bg-emerald-50 dark:bg-emerald-950/20' : 'bg-red-50 dark:bg-red-950/20'">
                                        <span :class="status === 'executed' ? 'bg-emerald-500' : 'bg-red-500'" class="w-1.5 h-1.5 rounded-full flex-shrink-0"></span>
                                        <span class="text-xs font-mono text-gray-600 dark:text-slate-400 flex-1 truncate" x-text="nodeId"></span>
                                        <span :class="status === 'executed' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'"
                                              class="text-[10px] font-bold uppercase" x-text="status"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-app-layout>
