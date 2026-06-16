<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="/workflows/{{ $workflowId }}" class="text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div>
                    <h1 class="text-base font-semibold text-gray-900 dark:text-white">Logs · <span id="wf-name">…</span></h1>
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Historique des exécutions nœud par nœud</p>
                </div>
            </div>
            <button id="btn-clear" class="hidden inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-slate-800 hover:bg-red-50 dark:hover:bg-red-950/30 text-red-600 dark:text-red-400 border border-red-300 dark:border-red-800/60 text-xs font-semibold rounded-lg transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                Vider les logs
            </button>
        </div>
    </x-slot>

    <div class="p-6" x-data="workflowLogs({{ $workflowId }})" x-init="init()">

        <template x-if="!loaded">
            <div class="flex items-center justify-center py-24">
                <svg class="w-6 h-6 animate-spin text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </div>
        </template>

        <template x-if="loaded && Object.keys(grouped).length === 0">
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 flex flex-col items-center justify-center py-20">
                <svg class="w-8 h-8 text-gray-300 dark:text-slate-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <p class="text-sm text-gray-500 dark:text-slate-400">Aucune exécution enregistrée.</p>
            </div>
        </template>

        <template x-if="loaded && Object.keys(grouped).length > 0">
            <div class="space-y-3">
                <template x-for="[execId, rows] in Object.entries(grouped)" :key="execId">
                    <div class="bg-white dark:bg-slate-900 rounded-xl overflow-hidden"
                         :class="rows.some(r => !r.status) ? 'border border-red-200 dark:border-red-900/50' : 'border border-gray-200 dark:border-slate-800'">

                        <!-- Execution header -->
                        <button @click="toggle(execId)"
                                class="w-full flex items-center justify-between px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-800/50 transition text-left">
                            <div class="flex items-center gap-3">
                                <span :class="rows.some(r => !r.status) ? 'bg-red-500' : 'bg-emerald-500'" class="w-2 h-2 rounded-full flex-shrink-0"></span>
                                <div>
                                    <span class="text-xs font-mono font-semibold text-gray-700 dark:text-slate-300" x-text="execId ? execId.substring(0, 8) + '…' : '—'"></span>
                                    <span class="ml-3 text-xs text-gray-400 dark:text-slate-500" x-text="rows[0]?.created_at ? new Date(rows[0].created_at).toLocaleString('fr-FR') : ''"></span>
                                    <span class="ml-3 text-xs font-medium" :class="rows.some(r => !r.status) ? 'text-red-500' : 'text-emerald-500'"
                                          x-text="rows.some(r => !r.status) ? 'Erreur' : 'Succès'"></span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <!-- Log entries count badge -->
                                <template x-if="rows.filter(r => r.action === 'action_log').length > 0">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 text-[10px] font-bold">
                                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        <span x-text="rows.filter(r => r.action === 'action_log').length + ' journal'"></span>
                                    </span>
                                </template>
                                <span class="text-xs text-gray-400 dark:text-slate-500" x-text="rows.length + ' nœud' + (rows.length > 1 ? 's' : '')"></span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="open[execId] ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </button>

                        <!-- Execution detail -->
                        <div x-show="open[execId]" x-transition.duration.150ms class="border-t border-gray-100 dark:border-slate-800 divide-y divide-gray-50 dark:divide-slate-800/70">
                            <template x-for="log in rows" :key="log.id">
                                <div>
                                    <!-- action_log: message journalisé en évidence -->
                                    <template x-if="log.action === 'action_log'">
                                        <div class="px-4 py-3" :class="log.status ? 'bg-indigo-50 dark:bg-indigo-950/20' : 'bg-red-50 dark:bg-red-950/20'">
                                            <div class="flex items-start gap-3">
                                                <div class="flex-shrink-0 w-7 h-7 rounded-md flex items-center justify-center mt-0.5"
                                                     :class="log.status ? 'bg-indigo-500 dark:bg-indigo-600' : 'bg-red-500'">
                                                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-1.5">
                                                        <span class="text-[11px] font-bold uppercase tracking-wide"
                                                              :class="log.status ? 'text-indigo-600 dark:text-indigo-400' : 'text-red-600 dark:text-red-400'">
                                                            Message journalisé
                                                        </span>
                                                        <span :class="log.status ? 'bg-indigo-200 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300' : 'bg-red-200 dark:bg-red-900/60 text-red-700 dark:text-red-300'"
                                                              class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-bold"
                                                              x-text="log.status ? 'OK' : 'Erreur'"></span>
                                                    </div>
                                                    <p class="text-sm font-medium leading-snug"
                                                       :class="log.status ? 'text-indigo-900 dark:text-indigo-100' : 'text-red-900 dark:text-red-100'"
                                                       x-text="log.message ?? '—'"></p>
                                                    <p class="text-[10px] mt-1.5 font-mono"
                                                       :class="log.status ? 'text-indigo-300 dark:text-indigo-600' : 'text-red-300 dark:text-red-600'"
                                                       x-text="(log.node_id ?? '').substring(0, 16)"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Tous les autres nœuds : ligne compacte -->
                                    <template x-if="log.action !== 'action_log'">
                                        <div class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-slate-800/30 transition text-xs">
                                            <!-- Icône type -->
                                            <div class="flex-shrink-0 w-5 h-5 rounded flex items-center justify-center text-gray-400 dark:text-slate-500"
                                                 x-html="actionIcon(log.action)"></div>
                                            <!-- Label -->
                                            <span class="w-28 flex-shrink-0 font-medium text-gray-700 dark:text-slate-200" x-text="labelFor(log.action)"></span>
                                            <!-- Statut -->
                                            <span :class="log.status
                                                    ? 'bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400'
                                                    : 'bg-red-100 dark:bg-red-950/40 text-red-700 dark:text-red-400'"
                                                  class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold flex-shrink-0"
                                                  x-text="log.status ? 'Succès' : 'Erreur'"></span>
                                            <!-- Message court -->
                                            <span class="text-gray-400 dark:text-slate-500 truncate flex-1" x-text="log.message ?? '—'"></span>
                                            <!-- Node ID discret -->
                                            <span class="font-mono text-[10px] text-gray-300 dark:text-slate-600 flex-shrink-0 hidden sm:block" x-text="(log.node_id ?? '').substring(0, 10)"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <script>
    function workflowLogs(id) {
        return {
            grouped: {},
            open: {},
            loaded: false,

            async init() {
                api(`/api/workflows/${id}`).then(w => {
                    document.getElementById('wf-name').textContent = w.name;
                });
                const data = await api(`/api/workflows/${id}/logs`);
                this.grouped = data;
                const firstKey = Object.keys(data)[0];
                if (firstKey) this.open[firstKey] = true;
                this.loaded = true;
                const btn = document.getElementById('btn-clear');
                btn.classList.remove('hidden');
                btn.addEventListener('click', () => this.clearLogs(id));
            },

            toggle(execId) {
                this.open[execId] = !this.open[execId];
            },

            async clearLogs(id) {
                if (!confirm('Vider tous les logs ? Cette action est irréversible.')) return;
                await api(`/api/workflows/${id}/logs`, { method: 'DELETE' });
                this.grouped = {};
            },

            labelFor(type) {
                const labels = {
                    'trigger_webhook':    'Webhook',
                    'trigger_form':       'Formulaire',
                    'trigger_scheduler':  'Planificateur',
                    'action_email':       'Email envoyé',
                    'action_log':         'Journal',
                    'action_delay':       'Délai',
                    'control_condition':  'Condition',
                };
                return labels[type] ?? type;
            },

            actionIcon(type) {
                const icons = {
                    'trigger_webhook':   '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
                    'trigger_form':      '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
                    'trigger_scheduler': '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                    'action_email':      '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
                    'action_delay':      '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                    'control_condition': '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                };
                return icons[type] ?? '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>';
            },
        };
    }
    </script>
</x-app-layout>
