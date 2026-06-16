<x-app-layout>
<meta name="csrf-token" content="{{ csrf_token() }}">

<x-slot name="header">
    <div class="flex justify-between items-center">
        <div class="flex items-center gap-3">
            <a href="{{ route('workflows.show', $workflow) }}" class="text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h2 class="font-semibold text-base text-gray-800 dark:text-gray-200">
                Éditeur : <span class="text-indigo-500">{{ $workflow->name }}</span>
            </h2>
        </div>
        <div class="flex items-center gap-2">
            <span id="save-status" class="text-xs text-gray-400 dark:text-slate-500 font-medium bg-gray-100 dark:bg-gray-900 px-3 py-1.5 rounded-full transition-all duration-300">Chargement…</span>
            <button onclick="activateWorkflow()" id="run-btn"
                    {{ $workflow->is_active ? 'disabled' : '' }}
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-md transition disabled:opacity-40 disabled:cursor-not-allowed">
                <span class="h-1.5 w-1.5 rounded-full bg-white"></span>Activer
            </button>
            <button onclick="deactivateWorkflow()" id="stop-btn"
                    {{ $workflow->is_active ? '' : 'disabled' }}
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-600 hover:bg-slate-700 text-white text-xs font-semibold rounded-md transition disabled:opacity-40 disabled:cursor-not-allowed">
                <span class="h-1.5 w-1.5 rounded-full bg-white"></span>Désactiver
            </button>
            <a href="{{ route('workflows.logs', $workflow) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-md transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Logs
            </a>
            <button onclick="clearCanvas()" class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-md transition">Vider</button>
        </div>
    </div>
</x-slot>

{{-- ── CONTEXT MENU ── --}}
<div id="ctx-menu"
     class="hidden fixed bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl shadow-xl z-50 p-2 w-52 space-y-1"
     role="menu">
    <p class="text-[10px] font-bold text-gray-400 uppercase px-2 py-1 tracking-wider">Ajouter dans la branche</p>
    <button onclick="ctxAdd('control_condition','Condition (Si/Sinon)')" role="menuitem" class="w-full text-left px-3 py-2 text-xs font-medium text-purple-700 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-950/30 rounded-lg transition">Condition (Si/Sinon)</button>
    <button onclick="ctxAdd('action_delay','Attendre (Délai)')" role="menuitem" class="w-full text-left px-3 py-2 text-xs font-medium text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-950/30 rounded-lg transition">Attendre (Délai)</button>
    <button onclick="ctxAdd('action_email','Envoyer un Email')" role="menuitem" class="w-full text-left px-3 py-2 text-xs font-medium text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 rounded-lg transition">Envoyer un Email</button>
    <button onclick="ctxAdd('action_log','Journaliser')" role="menuitem" class="w-full text-left px-3 py-2 text-xs font-medium text-slate-700 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-lg transition">Journaliser</button>
</div>

{{-- ── 3-PANEL LAYOUT ── --}}
<div class="flex overflow-hidden" style="height:calc(100vh - 57px)">

    {{-- LEFT: Palette --}}
    <aside class="w-52 flex-shrink-0 border-r border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-y-auto p-4 space-y-5">
        <div>
            <p class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-2">Déclencheurs</p>
            <div class="space-y-1.5">
                @foreach([
                    ['trigger_webhook','Webhook', 'indigo'],
                    ['trigger_scheduler','Planificateur', 'indigo'],
                    ['trigger_form','Formulaire', 'indigo'],
                ] as [$type,$label,$color])
                <button id="btn-{{ $type }}" draggable="true"
                        ondragstart="drag(event,'{{ $type }}','{{ $label }}')"
                        class="w-full text-left p-2.5 bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-900/50 rounded-lg text-indigo-700 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-950/40 transition text-xs font-medium cursor-grab active:cursor-grabbing">
                    {{ $label }}
                </button>
                @endforeach
            </div>
        </div>
        <div>
            <p class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-widest mb-2">Actions &amp; Logique</p>
            <div class="space-y-1.5">
                @foreach([
                    ['control_condition','Condition (Si/Sinon)','purple'],
                    ['action_delay','Attendre (Délai)','amber'],
                    ['action_email','Envoyer un Email','emerald'],
                    ['action_log','Journaliser','slate'],
                ] as [$type,$label,$color])
                <button id="btn-{{ $type }}" draggable="true"
                        ondragstart="drag(event,'{{ $type }}','{{ $label }}')"
                        class="w-full text-left p-2.5
                            @if($color==='purple') bg-purple-50/50 dark:bg-purple-950/20 border border-purple-200 dark:border-purple-900/50 text-purple-700 dark:text-purple-400 hover:bg-purple-100 dark:hover:bg-purple-950/40
                            @elseif($color==='amber') bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/50 text-amber-700 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-950/40
                            @elseif($color==='emerald') bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/50 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-950/40
                            @else bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800
                            @endif
                            rounded-lg transition text-xs font-medium cursor-grab active:cursor-grabbing">
                    {{ $label }}
                </button>
                @endforeach
            </div>
        </div>
        <div id="webhook-info" class="hidden" x-data="{copied:false}">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">URL Webhook</p>
            <div class="bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-lg p-2 space-y-1.5">
                <code class="text-[9px] font-mono text-indigo-600 dark:text-indigo-400 break-all block">{{ url('/api/webhook/'.$workflow->token) }}</code>
                <button @click="navigator.clipboard.writeText('{{ url('/api/webhook/'.$workflow->token) }}');copied=true;setTimeout(()=>copied=false,2000)"
                        class="w-full text-center text-[10px] font-semibold px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 hover:bg-indigo-100 transition">
                    <span x-text="copied?'✓ Copié !':'Copier'"></span>
                </button>
            </div>
        </div>
    </aside>

    {{-- CENTER: Canvas --}}
    <div class="flex-1 overflow-y-auto bg-gradient-to-br from-slate-50 to-indigo-50/30 dark:from-slate-900 dark:to-slate-950 relative"
         id="canvas-container"
         ondragover="allowDrop(event)"
         ondragenter="highlightCanvas(event,true)"
         ondragleave="highlightCanvas(event,false)"
         ondrop="dropRoot(event)">
        <p class="absolute top-4 left-4 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-slate-600 pointer-events-none">Logique du Scénario</p>
        <div id="canvas" class="flex flex-col items-center pt-10 pb-10 space-y-0 min-h-full">
            <div id="empty-msg" class="text-center my-auto py-20 text-gray-400 dark:text-slate-500">
                <p class="text-sm font-medium">Glissez un déclencheur ici pour démarrer</p>
                <p class="text-xs mt-1 opacity-60">Webhook, Formulaire ou Planificateur</p>
            </div>
        </div>
    </div>

    {{-- RIGHT: Config + Test (Alpine) --}}
    <aside class="w-80 flex-shrink-0 border-l border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex flex-col"
           x-data="editorPanel()">

        {{-- Tab bar --}}
        <div class="flex border-b border-gray-200 dark:border-slate-800 flex-shrink-0">
            <button @click="tab='config'"
                    :class="tab==='config' ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300'"
                    class="flex-1 text-xs font-semibold py-3 transition">⚙ Configuration</button>
            <button @click="tab='test'"
                    :class="tab==='test' ? 'border-b-2 border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-300'"
                    class="flex-1 text-xs font-semibold py-3 transition">▶ Tester</button>
        </div>

        {{-- ── CONFIG TAB ── --}}
        <div x-show="tab==='config'" class="flex-1 overflow-y-auto">

            {{-- Empty state --}}
            <div x-show="!selectedNodeId" class="flex flex-col items-center justify-center h-full text-center p-6 gap-3">
                <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-slate-800 flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/></svg>
                </div>
                <p class="text-sm text-gray-400 dark:text-slate-500">Cliquez sur un nœud pour le configurer</p>
            </div>

            {{-- Node config --}}
            <div x-show="selectedNodeId" class="p-4 space-y-4">

                {{-- Node title --}}
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider"
                          :class="nodeTypeBadgeClass()">
                        <span x-text="nodeTypeLabel()"></span>
                    </span>
                    <p class="text-sm font-semibold text-gray-800 dark:text-slate-200 truncate" x-text="selectedNode?.label || ''"></p>
                </div>

                {{-- ── trigger_webhook ── --}}
                <template x-if="selectedNode?.type === 'trigger_webhook'">
                    <div class="space-y-3">
                        <div class="bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-900/50 rounded-lg p-3">
                            <p class="text-[10px] font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider mb-1">URL d'écoute</p>
                            <code class="text-[9px] font-mono text-indigo-600 dark:text-indigo-400 break-all block">{{ url('/api/webhook/'.$workflow->token) }}</code>
                        </div>
                        <p class="text-[11px] text-gray-500 dark:text-slate-400">Donnez cette URL à un service externe (Stripe, GitHub, etc.). Il peut envoyer n'importe quel JSON — chaque clé devient une variable utilisable dans les nœuds suivants.</p>
                        <div>
                            <label class="cfg-label">Champs attendus <span class="text-gray-400">(autocomplétion)</span></label>
                            <input type="text" data-payload-key="expected_fields"
                                   :value="editPayload.expected_fields || ''"
                                   @input="updatePayload('expected_fields', $event.target.value)"
                                   placeholder="email,montant,nom,produit"
                                   class="cfg-input">
                            <p class="text-[10px] text-gray-400 mt-1">Séparez les noms des clés JSON par des virgules. Ils apparaîtront comme chips de variables dans la configuration des nœuds suivants.</p>
                        </div>
                        <template x-if="webhookDeclaredFields.length > 0">
                            <div class="bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-800/50 rounded-lg p-3">
                                <p class="text-[10px] font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider mb-2">Variables disponibles</p>
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="f in webhookDeclaredFields">
                                        <code class="text-[10px] bg-white dark:bg-slate-800 border border-indigo-300 dark:border-indigo-800 text-indigo-700 dark:text-indigo-400 px-1.5 py-0.5 rounded font-mono" x-text="'{' + '{ ' + f + ' }' + '}'"></code>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- ── trigger_form ── --}}
                <template x-if="selectedNode?.type === 'trigger_form'">
                    <div class="space-y-3">
                        <div>
                            <label class="cfg-label">Champs attendus (autocomplétion)</label>
                            <input type="text" data-payload-key="expected_fields"
                                   :value="editPayload.expected_fields || ''"
                                   @input="updatePayload('expected_fields', $event.target.value)"
                                   placeholder="email,nom,message,urgence"
                                   class="cfg-input">
                            <p class="text-[10px] text-gray-400 mt-1">Séparés par des virgules. Tous les champs du payload sont automatiquement disponibles — ce champ sert uniquement à l'autocomplétion dans l'éditeur.</p>
                        </div>
                        <template x-if="declaredFields.length > 0">
                            <div class="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/50 rounded-lg p-3">
                                <p class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-2">Variables disponibles</p>
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="f in declaredFields">
                                        <code class="text-[10px] bg-white dark:bg-slate-800 border border-emerald-300 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 px-1.5 py-0.5 rounded font-mono" x-text="'{' + '{ ' + f + ' }' + '}'"></code>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- ── trigger_scheduler ── --}}
                <template x-if="selectedNode?.type === 'trigger_scheduler'">
                    <div class="space-y-3">

                        {{-- Frequency type --}}
                        <div>
                            <label class="cfg-label">Fréquence</label>
                            <select @change="handleSchedulerTypeChange($event.target.value)" class="cfg-input">
                                <option value="minutely" :selected="schedulerType==='minutely'">Toutes les X minutes</option>
                                <option value="hourly"   :selected="schedulerType==='hourly'">Toutes les heures</option>
                                <option value="daily"    :selected="schedulerType==='daily'">Chaque jour à une heure fixe</option>
                                <option value="weekly"   :selected="schedulerType==='weekly'">Chaque semaine un jour précis</option>
                                <option value="custom"   :selected="schedulerType==='custom'">Personnalisé (cron avancé)</option>
                            </select>
                        </div>

                        {{-- Minutely --}}
                        <template x-if="schedulerType === 'minutely'">
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] text-gray-600 dark:text-slate-400 shrink-0">Toutes les</span>
                                <input type="number" min="1" max="59"
                                       x-model.number="schedulerMinutes"
                                       @change="applyScheduler()"
                                       class="cfg-input w-20 text-center">
                                <span class="text-[11px] text-gray-600 dark:text-slate-400 shrink-0">minutes</span>
                            </div>
                        </template>

                        {{-- Hourly --}}
                        <template x-if="schedulerType === 'hourly'">
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] text-gray-600 dark:text-slate-400 shrink-0">À</span>
                                <input type="number" min="0" max="59"
                                       x-model.number="schedulerHourMinute"
                                       @change="applyScheduler()"
                                       placeholder="0"
                                       class="cfg-input w-20 text-center">
                                <span class="text-[11px] text-gray-600 dark:text-slate-400 shrink-0">min passé chaque heure</span>
                            </div>
                        </template>

                        {{-- Daily --}}
                        <template x-if="schedulerType === 'daily'">
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] text-gray-600 dark:text-slate-400 shrink-0">À</span>
                                <input type="time" x-model="schedulerTime" @change="applyScheduler()" class="cfg-input w-32">
                                <span class="text-[11px] text-gray-600 dark:text-slate-400 shrink-0">chaque jour</span>
                            </div>
                        </template>

                        {{-- Weekly --}}
                        <template x-if="schedulerType === 'weekly'">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-[11px] text-gray-600 dark:text-slate-400 shrink-0">Le</span>
                                <select x-model.number="schedulerDay" @change="applyScheduler()" class="cfg-input">
                                    <option value="1">Lundi</option>
                                    <option value="2">Mardi</option>
                                    <option value="3">Mercredi</option>
                                    <option value="4">Jeudi</option>
                                    <option value="5">Vendredi</option>
                                    <option value="6">Samedi</option>
                                    <option value="0">Dimanche</option>
                                </select>
                                <span class="text-[11px] text-gray-600 dark:text-slate-400 shrink-0">à</span>
                                <input type="time" x-model="schedulerTime" @change="applyScheduler()" class="cfg-input w-28">
                            </div>
                        </template>

                        {{-- Custom cron --}}
                        <template x-if="schedulerType === 'custom'">
                            <div>
                                <label class="cfg-label">Expression cron</label>
                                <input type="text" data-payload-key="cron_expression"
                                       :value="editPayload.cron_expression || ''"
                                       @input="updatePayload('cron_expression', $event.target.value)"
                                       placeholder="0 9 * * 1"
                                       class="cfg-input font-mono">
                                <p class="text-[10px] text-gray-400 mt-1">Format : minute heure jour mois jour_semaine. Ex: <code class="bg-gray-100 dark:bg-slate-800 px-1 rounded">0 9 * * 1</code> = lundi à 9h</p>
                            </div>
                        </template>

                        {{-- Human-readable preview --}}
                        <div class="bg-purple-50 dark:bg-purple-950/20 border border-purple-200 dark:border-purple-800/50 rounded-lg p-3 space-y-1">
                            <p class="text-[10px] font-bold text-purple-700 dark:text-purple-400 uppercase tracking-wider">Récapitulatif</p>
                            <p class="text-[12px] text-purple-800 dark:text-purple-200 font-semibold" x-text="schedulerHumanLabel()"></p>
                            <p x-show="editPayload.cron_expression" class="text-[10px] font-mono text-purple-400" x-text="'cron : ' + (editPayload.cron_expression || '')"></p>
                        </div>

                        {{-- Timezone --}}
                        <div>
                            <label class="cfg-label">Fuseau horaire</label>
                            <select @change="updatePayload('timezone', $event.target.value)" class="cfg-input">
                                <option value="UTC" :selected="!editPayload.timezone || editPayload.timezone==='UTC'">UTC</option>
                                <option value="Europe/Paris" :selected="editPayload.timezone==='Europe/Paris'">Europe/Paris (heure française)</option>
                                <option value="America/New_York" :selected="editPayload.timezone==='America/New_York'">America/New_York</option>
                                <option value="Asia/Tokyo" :selected="editPayload.timezone==='Asia/Tokyo'">Asia/Tokyo</option>
                            </select>
                        </div>

                    </div>
                </template>

                {{-- ── action_email ── --}}
                <template x-if="selectedNode?.type === 'action_email'">
                    <div class="space-y-3">
                        <template x-if="availableVars.length === 0">
                            <p class="text-[11px] text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800/40 rounded-lg p-2.5">
                                Configurez d'abord le déclencheur pour voir les variables disponibles.
                            </p>
                        </template>
                        <div>
                            <label class="cfg-label">Destinataire</label>
                            <input type="text" id="email-to" data-payload-key="to"
                                   :value="editPayload.to || ''"
                                   @input="updatePayload('to', $event.target.value)"
                                   placeholder="&#123;&#123; email &#125;&#125;"
                                   class="cfg-input">
                            <div class="flex flex-wrap gap-1 mt-1" x-show="availableVars.length > 0">
                                <template x-for="v in availableVars">
                                    <button @mousedown.prevent @click="insertIntoField('email-to','to',v)"
                                            class="var-chip cursor-pointer hover:bg-indigo-100 dark:hover:bg-indigo-950/40 transition"
                                            x-text="'{' + '{ ' + v + ' }' + '}'"></button>
                                </template>
                            </div>
                        </div>
                        <div>
                            <label class="cfg-label">Sujet</label>
                            <input type="text" id="email-subject" data-payload-key="subject"
                                   :value="editPayload.subject || ''"
                                   @input="updatePayload('subject', $event.target.value)"
                                   placeholder="Nouveau message de &#123;&#123; nom &#125;&#125;"
                                   class="cfg-input">
                            <div class="flex flex-wrap gap-1 mt-1" x-show="availableVars.length > 0">
                                <template x-for="v in availableVars">
                                    <button @mousedown.prevent @click="insertIntoField('email-subject','subject',v)"
                                            class="var-chip cursor-pointer hover:bg-indigo-100 dark:hover:bg-indigo-950/40 transition"
                                            x-text="'{' + '{ ' + v + ' }' + '}'"></button>
                                </template>
                            </div>
                        </div>
                        <div>
                            <label class="cfg-label">Corps du message</label>
                            <textarea id="email-message" data-payload-key="message" rows="5"
                                      @input="updatePayload('message', $event.target.value)"
                                      :value="editPayload.message || ''"
                                      placeholder="Bonjour &#123;&#123; nom &#125;&#125;,&#10;Votre message a été reçu."
                                      class="cfg-input resize-none"></textarea>
                            <div class="flex flex-wrap gap-1 mt-1" x-show="availableVars.length > 0">
                                <template x-for="v in availableVars">
                                    <button @mousedown.prevent @click="insertIntoField('email-message','message',v)"
                                            class="var-chip cursor-pointer hover:bg-indigo-100 dark:hover:bg-indigo-950/40 transition"
                                            x-text="'{' + '{ ' + v + ' }' + '}'"></button>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- ── action_log ── --}}
                <template x-if="selectedNode?.type === 'action_log'">
                    <div class="space-y-3">
                        <div>
                            <label class="cfg-label">Message à journaliser</label>
                            <textarea id="log-message" data-payload-key="message" rows="4"
                                      @input="updatePayload('message', $event.target.value)"
                                      :value="editPayload.message || ''"
                                      placeholder="Commande reçue : &#123;&#123; commande &#125;&#125; par &#123;&#123; client &#125;&#125;"
                                      class="cfg-input resize-none"></textarea>
                            <div class="flex flex-wrap gap-1 mt-1" x-show="availableVars.length > 0">
                                <template x-for="v in availableVars">
                                    <button @mousedown.prevent @click="insertIntoField('log-message','message',v)"
                                            class="var-chip cursor-pointer hover:bg-indigo-100 dark:hover:bg-indigo-950/40 transition"
                                            x-text="'{' + '{ ' + v + ' }' + '}'"></button>
                                </template>
                            </div>
                        </div>
                        <p class="text-[11px] text-gray-400">Le message est visible dans les journaux d'exécution après le test.</p>
                    </div>
                </template>

                {{-- ── control_condition ── --}}
                <template x-if="selectedNode?.type === 'control_condition'">
                    <div class="space-y-3">
                        <div>
                            <label class="cfg-label">Variable à tester</label>
                            <input type="text" data-payload-key="variable"
                                   :value="editPayload.variable || ''"
                                   @input="updatePayload('variable', $event.target.value)"
                                   placeholder="montant"
                                   class="cfg-input">
                            <p class="text-[10px] text-gray-400 mt-1">Nom de la variable du payload (sans les accolades).</p>
                        </div>
                        <div x-show="availableVars.length > 0">
                            <p class="cfg-label">Variables disponibles</p>
                            <div class="flex flex-wrap gap-1 mt-1">
                                <template x-for="v in availableVars">
                                    <button @click="updatePayload('variable', v)" class="var-chip cursor-pointer hover:bg-indigo-100 dark:hover:bg-indigo-950/40 transition" x-text="v"></button>
                                </template>
                            </div>
                        </div>
                        <div>
                            <label class="cfg-label">Opérateur</label>
                            <select @change="updatePayload('operator', $event.target.value)" class="cfg-input"
                                    x-init="if (!editPayload.operator) updatePayload('operator', 'equals')">
                                <option value="equals"           :selected="!editPayload.operator || editPayload.operator==='equals'">égal à (==)</option>
                                <option value="not_equals"       :selected="editPayload.operator==='not_equals'">différent de (!=)</option>
                                <option value="greater_than"     :selected="editPayload.operator==='greater_than'">supérieur à (&gt;)</option>
                                <option value="greater_or_equal" :selected="editPayload.operator==='greater_or_equal'">supérieur ou égal (&gt;=)</option>
                                <option value="less_than"        :selected="editPayload.operator==='less_than'">inférieur à (&lt;)</option>
                                <option value="less_or_equal"    :selected="editPayload.operator==='less_or_equal'">inférieur ou égal (&lt;=)</option>
                                <option value="contains"         :selected="editPayload.operator==='contains'">contient</option>
                            </select>
                        </div>
                        <div>
                            <label class="cfg-label">Valeur de comparaison</label>
                            <input type="text" data-payload-key="value"
                                   :value="editPayload.value || ''"
                                   @input="updatePayload('value', $event.target.value)"
                                   placeholder="100"
                                   class="cfg-input">
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-950/20 border border-purple-200 dark:border-purple-800/50 rounded-lg p-3 text-[11px] text-purple-700 dark:text-purple-400">
                            <span x-show="editPayload.variable && editPayload.operator && editPayload.value">
                                Condition : <strong x-text="editPayload.variable + ' ' + operatorLabel() + ' ' + editPayload.value"></strong>
                            </span>
                            <span x-show="!editPayload.variable || !editPayload.operator || !editPayload.value">
                                Configurez les trois champs pour voir la condition.
                            </span>
                        </div>
                    </div>
                </template>

                {{-- ── action_delay ── --}}
                <template x-if="selectedNode?.type === 'action_delay'">
                    <div class="space-y-3">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="cfg-label">Durée</label>
                                <input type="number" min="1" data-payload-key="duration"
                                       :value="editPayload.duration || ''"
                                       @input="updatePayload('duration', +$event.target.value)"
                                       placeholder="5"
                                       class="cfg-input">
                            </div>
                            <div>
                                <label class="cfg-label">Unité</label>
                                <select @change="updatePayload('unit', $event.target.value)" class="cfg-input">
                                    <option value="seconds" :selected="!editPayload.unit || editPayload.unit==='seconds'">Secondes</option>
                                    <option value="minutes" :selected="editPayload.unit==='minutes'">Minutes</option>
                                    <option value="hours"   :selected="editPayload.unit==='hours'">Heures</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </template>

            </div>
        </div>

        {{-- ── TEST TAB ── --}}
        <div x-show="tab==='test'" class="flex-1 overflow-y-auto p-4 space-y-4">

            {{-- Trigger info banner --}}
            <div x-show="!triggerNode" class="text-center py-6 text-gray-400 dark:text-slate-500">
                <p class="text-sm">Ajoutez d'abord un déclencheur sur le canvas.</p>
            </div>

            <template x-if="triggerNode">
                <div class="space-y-4">

                    {{-- Scheduler auto-fire notice (shown when scheduler + another trigger in chain) --}}
                    <template x-if="hasSchedulerInChain && triggerNodes.length > 1">
                        <div class="bg-purple-50 dark:bg-purple-950/20 border border-purple-200 dark:border-purple-800/50 rounded-lg p-3 space-y-1.5">
                            <p class="text-[11px] font-bold text-purple-700 dark:text-purple-300">⏱ Planificateur — déclenché automatiquement en premier</p>
                            <p class="text-[11px] text-purple-600 dark:text-purple-400">Il s'exécute avant le déclencheur suivant et injecte ses variables dans le flux. Configurez ci-dessous les données du déclencheur suivant.</p>
                            <div class="flex gap-1 flex-wrap pt-0.5">
                                <code class="var-chip">&#123;&#123; triggered_at &#125;&#125;</code>
                                <code class="var-chip">&#123;&#123; scheduled_time &#125;&#125;</code>
                            </div>
                        </div>
                    </template>

                    {{-- Trigger badge --}}
                    <div class="rounded-lg border p-3"
                         :class="{
                           'bg-indigo-50 border-indigo-200 dark:bg-indigo-950/20 dark:border-indigo-900/50': triggerNode.type==='trigger_webhook',
                           'bg-blue-50 border-blue-200 dark:bg-blue-950/20 dark:border-blue-900/50': triggerNode.type==='trigger_form',
                           'bg-purple-50 border-purple-200 dark:bg-purple-950/20 dark:border-purple-900/50': triggerNode.type==='trigger_scheduler',
                         }">
                        <p class="text-[10px] font-bold uppercase tracking-wider mb-1"
                           :class="{
                             'text-indigo-600 dark:text-indigo-400': triggerNode.type==='trigger_webhook',
                             'text-blue-600 dark:text-blue-400': triggerNode.type==='trigger_form',
                             'text-purple-600 dark:text-purple-400': triggerNode.type==='trigger_scheduler',
                           }"
                           x-text="triggerNode.label"></p>
                    </div>

                    {{-- Webhook data editor --}}
                    <template x-if="triggerNode.type==='trigger_webhook'">
                        <div class="space-y-2">
                            <label class="cfg-label">Données reçues par le webhook (JSON)</label>
                            <textarea x-model="webhookPayload" rows="6"
                                      class="cfg-input font-mono text-[11px] resize-none"
                                      placeholder='{"email":"marie@exemple.com","montant":250}'></textarea>
                            <template x-if="webhookDeclaredFields.length > 0">
                                <div class="bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-200 dark:border-indigo-800/50 rounded-lg p-2.5 space-y-1.5">
                                    <p class="text-[10px] font-bold text-indigo-700 dark:text-indigo-400">Champs déclarés dans la config :</p>
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="f in webhookDeclaredFields">
                                            <code class="var-chip" x-text="f"></code>
                                        </template>
                                    </div>
                                    <button @click="webhookPayload = JSON.stringify(Object.fromEntries(webhookDeclaredFields.map(f => [f, ''])), null, 2)"
                                            class="text-[10px] text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                                        ↺ Générer un modèle JSON
                                    </button>
                                </div>
                            </template>
                            <div class="flex flex-wrap gap-1">
                                <p class="text-[10px] text-gray-400 w-full">Exemples rapides :</p>
                                <button @click="webhookPayload='{&quot;email&quot;:&quot;client@exemple.com&quot;,&quot;nom&quot;:&quot;Marie&quot;,&quot;montant&quot;:250,&quot;produit&quot;:&quot;Premium&quot;}'" class="quick-example">Commande VIP</button>
                                <button @click="webhookPayload='{&quot;email&quot;:&quot;client@exemple.com&quot;,&quot;nom&quot;:&quot;Jean&quot;,&quot;montant&quot;:49,&quot;produit&quot;:&quot;Basic&quot;}'" class="quick-example">Commande standard</button>
                                <button @click="webhookPayload='{&quot;event&quot;:&quot;push&quot;,&quot;repo&quot;:&quot;mon-projet&quot;,&quot;author&quot;:&quot;dev@acme.com&quot;}'" class="quick-example">GitHub</button>
                            </div>
                        </div>
                    </template>

                    {{-- Form fields editor --}}
                    <template x-if="triggerNode.type==='trigger_form'">
                        <div class="space-y-2">
                            <template x-if="declaredFields.length > 0">
                                <div class="space-y-3">
                                    <template x-for="field in declaredFields">
                                        <div>
                                            <label class="cfg-label flex items-center gap-1.5">
                                                <span x-text="field"></span>
                                                <code class="text-[9px] bg-indigo-50 dark:bg-indigo-950/30 text-indigo-500 px-1 rounded font-mono normal-case font-normal" x-text="'{' + '{ ' + field + ' }' + '}'"></code>
                                            </label>
                                            <input type="text"
                                                   :placeholder="'ex: une valeur pour ' + field"
                                                   @input="formFields[field] = $event.target.value"
                                                   :value="formFields[field] || ''"
                                                   class="cfg-input">
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <template x-if="declaredFields.length === 0">
                                <div class="bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-lg p-3">
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Collez directement un JSON dans le payload ci-dessous, ou déclarez des champs dans l'onglet Configuration pour avoir des inputs guidés.</p>
                                </div>
                            </template>
                        </div>
                    </template>

                    {{-- Scheduler: next-run display + simulate button --}}
                    <template x-if="triggerNode.type==='trigger_scheduler'">
                        <div class="space-y-3">

                            {{-- Next run card --}}
                            <div class="bg-purple-50 dark:bg-purple-950/20 border border-purple-200 dark:border-purple-800/50 rounded-lg p-3 space-y-2">
                                <div class="flex items-center justify-between">
                                    <p class="text-[10px] font-bold text-purple-700 dark:text-purple-400 uppercase tracking-wider">⏱ Prochain déclenchement</p>
                                    <button @click="fetchNextRun()" class="text-[10px] text-purple-500 hover:text-purple-700 dark:hover:text-purple-300 font-medium transition">↺ Rafraîchir</button>
                                </div>
                                <template x-if="schedulerLoading">
                                    <p class="text-[11px] text-purple-500 animate-pulse">Calcul en cours…</p>
                                </template>
                                <template x-if="!schedulerLoading && schedulerNextRun?.error">
                                    <p class="text-[11px] text-red-600 dark:text-red-400 font-medium" x-text="schedulerNextRun.error"></p>
                                </template>
                                <template x-if="!schedulerLoading && !schedulerNextRun">
                                    <p class="text-[11px] text-purple-400">Cliquez sur ↺ pour calculer.</p>
                                </template>
                                <template x-if="!schedulerLoading && schedulerNextRun?.next_run">
                                    <div class="space-y-0.5">
                                        <p class="text-sm font-bold text-purple-800 dark:text-purple-200" x-text="schedulerNextRun.next_run_human"></p>
                                        <p class="text-[10px] font-mono text-purple-400" x-text="schedulerNextRun.next_run"></p>
                                    </div>
                                </template>
                                <template x-if="!schedulerLoading && schedulerNextRun?.next_run && schedulerNextRun?.valid">
                                    <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">✅ Workflow valide — prêt à être activé</p>
                                </template>
                                <template x-if="!schedulerLoading && schedulerNextRun?.next_run && schedulerNextRun?.valid === false">
                                    <p class="text-[10px] text-red-600 dark:text-red-400 font-semibold" x-text="'❌ ' + (schedulerNextRun?.errors?.[0] || 'Erreur de validation')"></p>
                                </template>
                            </div>

                            {{-- Simulate button --}}
                            <button @click="runTest()"
                                    :disabled="testRunning"
                                    class="w-full py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-sm font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                                <svg x-show="testRunning" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                                <span x-text="testRunning ? 'Simulation…' : '▶ Simuler l\'exécution maintenant'"></span>
                            </button>
                            <p class="text-[10px] text-gray-400 dark:text-slate-500 text-center -mt-1">Exécute le workflow immédiatement pour tester la logique, sans attendre la planification.</p>
                        </div>
                    </template>

                    {{-- Run button (webhook / form only) --}}
                    <template x-if="triggerNode.type !== 'trigger_scheduler'">
                        <button @click="runTest()"
                                :disabled="testRunning"
                                class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                            <svg x-show="testRunning" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/></svg>
                            <span x-text="testRunning ? 'Exécution…' : 'Lancer le test'"></span>
                        </button>
                    </template>

                    {{-- Test result --}}
                    <template x-if="testResult">
                        <div class="space-y-3">
                            {{-- Overall status --}}
                            <div :class="testResult.success ? 'bg-emerald-50 border-emerald-200 dark:bg-emerald-950/20 dark:border-emerald-800/50' : 'bg-red-50 border-red-200 dark:bg-red-950/20 dark:border-red-800/50'"
                                 class="border rounded-lg p-3">
                                <p :class="testResult.success ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400'"
                                   class="text-xs font-bold" x-text="testResult.success ? '✓ Exécution réussie' : '✗ Exécution échouée'"></p>
                                <p x-show="testResult.result?.error" class="text-[11px] text-red-600 dark:text-red-400 mt-1" x-text="testResult.result?.error"></p>
                            </div>

                            {{-- Per-node status --}}
                            <div class="space-y-1">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Nœuds exécutés</p>
                                <template x-for="[nodeId, status] in Object.entries(testResult.result?.path || {})">
                                    <div class="flex items-center gap-2 py-1">
                                        <span :class="status==='executed' ? 'bg-emerald-500' : 'bg-red-500'" class="h-1.5 w-1.5 rounded-full flex-shrink-0"></span>
                                        <span class="text-[11px] text-gray-600 dark:text-slate-400 truncate" x-text="nodeLabel(nodeId) + ' — ' + (status==='executed' ? 'OK' : 'Erreur')"></span>
                                    </div>
                                </template>
                            </div>

                            {{-- Logs link --}}
                            <a href="{{ route('workflows.logs', $workflow) }}" class="block text-center text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                Voir les journaux complets →
                            </a>
                        </div>
                    </template>

                </div>
            </template>
        </div>

    </aside>
</div>

<style>
.cfg-label  { @apply block text-[11px] font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wider mb-1; }
.cfg-input  { @apply w-full text-xs bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-700 rounded-lg px-3 py-2 text-slate-800 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-600 transition; }
.var-chip   { @apply text-[10px] bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-400 px-1.5 py-0.5 rounded font-mono; }
.cron-example { @apply block w-full text-left text-[10px] font-mono text-gray-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 py-0.5 transition; }
.quick-example { @apply text-[10px] px-2 py-0.5 bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-400 hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-indigo-950/30 dark:hover:text-indigo-400 rounded transition; }
</style>

<script>
// ── Type mappings ─────────────────────────────────────────────────────────────
const EDITOR_TO_BACKEND = {
    'control_condition': 'control_condition',
    'action_delay':      'action_delay',
    'trigger_scheduler': 'trigger_scheduler',
};
function toBackendType(t) { return EDITOR_TO_BACKEND[t] || t; }
function toEditorType(t)  { return t; } // backend types ARE the editor types now

function getNodeLabel(type) {
    const map = {
        'trigger_webhook':   'Webhook Récepteur',
        'trigger_scheduler': 'Planificateur',
        'trigger_form':      'Formulaire Soumis',
        'control_condition': 'Condition (Si/Sinon)',
        'action_delay':      'Attendre (Délai)',
        'action_email':      'Envoyer un Email',
        'action_log':        'Journaliser',
    };
    return map[type] || type;
}

// ── Global state ──────────────────────────────────────────────────────────────
const WORKFLOW_ID   = {{ $workflow->id }};
const CACHE_KEY     = 'wf_cache_' + WORKFLOW_ID;
const rawStructure  = @json($workflow->nodes_structure ?? ['nodes'=>[],'edges'=>[],'meta'=>['startNodeId'=>null]]);

window.nodesList    = graphToTree(rawStructure);
window.lastExecPath = {};
window.selectedNodeId = null;

let saveTimeout = null;
let ctxTarget   = null;

// ── Graph ↔ Tree conversion ───────────────────────────────────────────────────
function graphToTree(struct) {
    if (!struct?.nodes?.length) return [];
    const { nodes = [], edges = [], meta = {} } = struct;
    const startId = meta.startNodeId;
    if (!startId) return [];

    const nodeMap = {};
    nodes.forEach(n => {
        nodeMap[n.id] = { id: n.id, type: n.type, label: getNodeLabel(n.type), payload: n.payload || {}, trueBranch: [], falseBranch: [] };
    });
    const outEdges = {};
    edges.forEach(e => { (outEdges[e.source] = outEdges[e.source] || []).push(e); });

    function buildChain(id) {
        const chain = []; let cur = id; const seen = new Set();
        while (cur && !seen.has(cur)) {
            seen.add(cur);
            const node = { ...nodeMap[cur] };
            if (!node.type) break;
            if (node.type === 'control_condition') {
                const outs = outEdges[cur] || [];
                const te = outs.find(e => e.condition === 'true');
                const fe = outs.find(e => e.condition === 'false');
                if (te) node.trueBranch  = buildChain(te.target);
                if (fe) node.falseBranch = buildChain(fe.target);
            }
            chain.push(node);
            const next = (outEdges[cur] || []).find(e => !e.condition);
            cur = next ? next.target : null;
        }
        return chain;
    }

    // Full chain from start — triggers chained sequentially, no secondary prepend needed
    return buildChain(startId);
}

function treeToGraph(list) {
    const flatNodes = [], edges = [];
    let startNodeId = null;

    function process(seq, parentId, parentCond) {
        seq.forEach((node, i) => {
            flatNodes.push({ id: node.id, type: node.type, payload: node.payload || {} });
            if (!startNodeId) startNodeId = node.id;   // first node in list = start
            const src  = i === 0 ? parentId : seq[i - 1].id;
            const cond = i === 0 ? parentCond : null;
            if (src !== null) {
                const edge = { source: src, target: node.id };
                if (cond) edge.condition = cond;
                edges.push(edge);
            }
            if (node.type === 'control_condition') {
                if (node.trueBranch?.length)  process(node.trueBranch,  node.id, 'true');
                if (node.falseBranch?.length) process(node.falseBranch, node.id, 'false');
            }
        });
    }

    process(list, null, null);
    return { nodes: flatNodes, edges, meta: { startNodeId } };
}

// ── Node helpers ──────────────────────────────────────────────────────────────
function findNodeInTree(list, id) {
    for (const n of list) {
        if (n.id === id) return n;
        if (n.type === 'control_condition') {
            const f = findNodeInTree(n.trueBranch, id) || findNodeInTree(n.falseBranch, id);
            if (f) return f;
        }
    }
    return null;
}

function updateNodePayload(nodeId, key, value) {
    function update(list) {
        for (const n of list) {
            if (n.id === nodeId) { n.payload = n.payload || {}; n.payload[key] = value; return true; }
            if (n.type === 'control_condition') { if (update(n.trueBranch) || update(n.falseBranch)) return true; }
        }
        return false;
    }
    update(window.nodesList);
    triggerAutoSave();
}

// ── Selection ─────────────────────────────────────────────────────────────────
function selectNode(nodeId) {
    window.selectedNodeId = nodeId;
    document.querySelectorAll('[data-node-id]').forEach(el => {
        const isSelected = el.dataset.nodeId === nodeId;
        el.classList.toggle('ring-2', isSelected);
        el.classList.toggle('ring-indigo-400', isSelected);
        el.classList.toggle('dark:ring-indigo-500', isSelected);
    });
    window.dispatchEvent(new CustomEvent('node-selected', { detail: { nodeId } }));
}

// ── Drag & Drop ───────────────────────────────────────────────────────────────
function drag(ev, type, label) {
    const isTrigger = type.startsWith('trigger_');
    if (window.nodesList.length === 0 && !isTrigger) return ev.preventDefault();
    if (isTrigger) {
        const existing = window.nodesList.filter(n => n.type.startsWith('trigger_'));
        if (type === 'trigger_scheduler') {
            // One scheduler max; can coexist with webhook/form
            if (existing.some(n => n.type === 'trigger_scheduler')) return ev.preventDefault();
        } else {
            // Webhook / Form: mutually exclusive with each other (scheduler allowed alongside)
            if (existing.some(n => n.type !== 'trigger_scheduler')) return ev.preventDefault();
        }
    }
    ev.dataTransfer.effectAllowed = 'move';
    ev.dataTransfer.setData('text/plain', JSON.stringify({ type, label }));
}
function allowDrop(ev) { ev.preventDefault(); }
function highlightCanvas(ev, on) {
    ev.preventDefault();
    document.getElementById('canvas-container').classList.toggle('ring-2', on);
    document.getElementById('canvas-container').classList.toggle('ring-indigo-500/30', on);
}
function dropRoot(ev) {
    ev.preventDefault();
    document.getElementById('canvas-container').classList.remove('ring-2','ring-indigo-500/30');
    if (ev.target.closest('.branch-zone')) return;
    try {
        const data    = JSON.parse(ev.dataTransfer.getData('text/plain'));
        const newNode = makeNode(data.type, data.label);
        if (data.type.startsWith('trigger_')) {
            // Triggers always go before actions
            const firstActionIdx = window.nodesList.findIndex(n => !n.type.startsWith('trigger_'));
            if (firstActionIdx === -1) window.nodesList.push(newNode);
            else window.nodesList.splice(firstActionIdx, 0, newNode);
        } else {
            window.nodesList.push(newNode);
        }
        renderNodes(); triggerAutoSave();
    } catch(e) { console.error(e); }
}
function dropBranch(ev, parentId, branch) {
    ev.preventDefault(); ev.stopPropagation();
    ev.currentTarget.classList.remove('border-indigo-500','bg-indigo-50/50');
    try {
        const data = JSON.parse(ev.dataTransfer.getData('text/plain'));
        if (data.type.startsWith('trigger')) { return; }
        insertIntoTree(window.nodesList, parentId, branch, makeNode(data.type, data.label));
        renderNodes(); triggerAutoSave();
    } catch(e) { console.error(e); }
}
function insertIntoTree(list, parentId, branch, newNode) {
    for (const n of list) {
        if (n.id === parentId) { n[branch].push(newNode); return true; }
        if (n.type === 'control_condition') {
            if (insertIntoTree(n.trueBranch, parentId, branch, newNode)) return true;
            if (insertIntoTree(n.falseBranch, parentId, branch, newNode)) return true;
        }
    }
    return false;
}

// ── Context menu ──────────────────────────────────────────────────────────────
function openCtxMenu(ev, parentId, branch) {
    ev.stopPropagation(); ctxTarget = { parentId, branch };
    const menu = document.getElementById('ctx-menu');
    menu.classList.remove('hidden');
    menu.style.top  = `${window.scrollY + ev.currentTarget.getBoundingClientRect().bottom + 6}px`;
    menu.style.left = `${window.scrollX + ev.currentTarget.getBoundingClientRect().left}px`;
}
function ctxAdd(type, label) {
    if (ctxTarget) { insertIntoTree(window.nodesList, ctxTarget.parentId, ctxTarget.branch, makeNode(type, label)); renderNodes(); triggerAutoSave(); }
    closeCtxMenu();
}
function closeCtxMenu() { document.getElementById('ctx-menu').classList.add('hidden'); ctxTarget = null; }
document.addEventListener('click', closeCtxMenu);

// ── Node management ───────────────────────────────────────────────────────────
function makeNode(type, label) {
    return { id: 'node_' + Date.now() + Math.random().toString(36).substr(2,4), type, label: label || getNodeLabel(type), payload: {}, trueBranch: [], falseBranch: [] };
}
function removeNode(id) {
    function filter(list) {
        return list.filter(n => {
            if (n.id === id) return false;
            if (n.type === 'control_condition') { n.trueBranch = filter(n.trueBranch); n.falseBranch = filter(n.falseBranch); }
            return true;
        });
    }
    window.nodesList = filter(window.nodesList);
    if (window.selectedNodeId === id) {
        window.selectedNodeId = null;
        window.dispatchEvent(new CustomEvent('node-deselected'));
    }
    renderNodes(); triggerAutoSave();
}
function clearCanvas() {
    if (!confirm('Réinitialiser complètement l\'éditeur ?')) return;
    window.nodesList = []; window.lastExecPath = {};
    window.selectedNodeId = null;
    window.dispatchEvent(new CustomEvent('node-deselected'));
    renderNodes(); triggerAutoSave();
}

// ── Workflow activate / deactivate ────────────────────────────────────────────
function activateWorkflow() {
    setSaveStatus('Activation…','amber');
    api('/api/workflows/' + WORKFLOW_ID + '/activate', { method:'POST', body:'{}' })
    .then(() => { setSaveStatus('Workflow actif','emerald'); document.getElementById('run-btn').setAttribute('disabled',''); document.getElementById('stop-btn').removeAttribute('disabled'); })
    .catch(() => setSaveStatus('Erreur activation','red'));
}
function deactivateWorkflow() {
    setSaveStatus('Désactivation…','amber');
    api('/api/workflows/' + WORKFLOW_ID + '/deactivate', { method:'POST', body:'{}' })
    .then(() => { setSaveStatus('Workflow inactif','slate'); document.getElementById('stop-btn').setAttribute('disabled',''); document.getElementById('run-btn').removeAttribute('disabled'); })
    .catch(() => setSaveStatus('Erreur','red'));
}
function setSaveStatus(text, color) {
    const el = document.getElementById('save-status');
    el.textContent = text;
    el.className = 'text-xs font-medium px-3 py-1.5 rounded-full transition-all duration-300 ' + {
        amber:   'text-amber-700 dark:text-amber-300 bg-amber-100 dark:bg-amber-950/30',
        emerald: 'text-emerald-700 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-950/30',
        red:     'text-red-700 dark:text-red-300 bg-red-100 dark:bg-red-950/30',
        slate:   'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800',
        indigo:  'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/20',
    }[color] || '';
}

// ── Auto-save ─────────────────────────────────────────────────────────────────
function triggerAutoSave() {
    setSaveStatus('Sauvegarde…','amber');
    clearTimeout(saveTimeout);
    saveTimeout = setTimeout(() => {
        api('/api/workflows/' + WORKFLOW_ID + '/save', { method:'POST', body: JSON.stringify(treeToGraph(window.nodesList)) })
        .then(() => setSaveStatus('Sauvegardé','emerald'))
        .catch(() => setSaveStatus('Erreur sauvegarde','red'));
    }, 600);
}

// ── Execution coloring ────────────────────────────────────────────────────────
function colorCanvas(path) {
    window.lastExecPath = path || {};
    renderNodes();
}

// ── SVG arrow ─────────────────────────────────────────────────────────────────
function injectArrow(parent) {
    const svg = document.createElementNS('http://www.w3.org/2000/svg','svg');
    svg.setAttribute('width','24'); svg.setAttribute('height','32'); svg.setAttribute('viewBox','0 0 24 32');
    svg.className = 'my-1 flex-shrink-0'; svg.setAttribute('aria-hidden','true');
    const line = document.createElementNS('http://www.w3.org/2000/svg','line');
    line.setAttribute('x1','12'); line.setAttribute('y1','0'); line.setAttribute('x2','12'); line.setAttribute('y2','24');
    line.setAttribute('stroke','#4f46e5'); line.setAttribute('stroke-width','2.5'); line.setAttribute('stroke-linecap','round');
    const poly = document.createElementNS('http://www.w3.org/2000/svg','polygon');
    poly.setAttribute('points','12,32 6,20 18,20'); poly.setAttribute('fill','#4f46e5');
    svg.appendChild(line); svg.appendChild(poly); parent.appendChild(svg);
}

// ── Node summary (shown on canvas card) ──────────────────────────────────────
function nodeSummary(node) {
    const p = node.payload || {};
    switch (node.type) {
        case 'trigger_form':      return p.expected_fields ? p.expected_fields.split(',').map(f=>f.trim()).join(', ') : 'Reçoit n\'importe quel JSON';
        case 'trigger_webhook':   return 'Reçoit n\'importe quel JSON';
        case 'trigger_scheduler': return p.cron_expression || 'Expression cron non définie';
        case 'action_email':      return p.to ? '→ ' + p.to : 'Destinataire non configuré';
        case 'action_log':        return p.message ? p.message.substring(0,40) + (p.message.length>40?'…':'') : 'Message non configuré';
        case 'control_condition': return (p.variable && p.operator && p.value) ? p.variable + ' ' + p.operator.replace('_',' ') + ' ' + p.value : 'Condition non configurée';
        case 'action_delay':      return (p.duration && p.unit) ? p.duration + ' ' + p.unit : 'Délai non configuré';
        default: return '';
    }
}

// ── Build node card ───────────────────────────────────────────────────────────
function buildNodeHTML(node, index, isSub = false) {
    const wrapper = document.createElement('div');
    wrapper.className = 'w-full flex flex-col items-center mt-1';

    const execStatus = window.lastExecPath[node.id];
    const isSelected = window.selectedNodeId === node.id;

    const typeThemes = {
        'trigger_webhook':   'border-indigo-400 hover:border-indigo-500',
        'trigger_scheduler': 'border-indigo-400 hover:border-indigo-500',
        'trigger_form':      'border-indigo-400 hover:border-indigo-500',
        'control_condition': 'border-purple-400 hover:border-purple-500',
        'action_delay':      'border-amber-400 hover:border-amber-500',
        'action_email':      'border-emerald-400 hover:border-emerald-500',
        'action_log':        'border-slate-400 hover:border-slate-500',
    };

    const execRing = execStatus === 'executed' ? ' ring-2 ring-emerald-400 dark:ring-emerald-500'
                   : execStatus === 'error'    ? ' ring-2 ring-red-400 dark:ring-red-500'
                   : '';
    const selRing  = isSelected ? ' ring-2 ring-indigo-400 dark:ring-indigo-500' : '';

    const sizing = isSub ? 'w-full max-w-xs p-3 text-xs' : 'w-full max-w-lg p-3.5 text-sm';
    const border  = typeThemes[node.type] || 'border-gray-400';

    const block = document.createElement('div');
    block.className = `${sizing} border-2 ${border} bg-white dark:bg-slate-900 rounded-xl flex flex-col gap-1 transition-all duration-150 cursor-pointer${execRing}${selRing}`;
    block.dataset.nodeId = node.id;

    const typeLabels = { 'trigger_': 'Déclencheur', 'control_': 'Logique', 'action_': 'Action' };
    const typeCategory = Object.entries(typeLabels).find(([prefix]) => node.type.startsWith(prefix))?.[1] || '';

    const execDot = execStatus === 'executed' ? '<span class="inline-block h-2 w-2 rounded-full bg-emerald-400 ml-1"></span>'
                  : execStatus === 'error'    ? '<span class="inline-block h-2 w-2 rounded-full bg-red-400 ml-1"></span>'
                  : '';

    const summary = nodeSummary(node);
    block.innerHTML = `
        <div class="flex justify-between items-start gap-2">
            <div class="flex items-center gap-2 min-w-0">
                <span class="text-[9px] bg-slate-100 dark:bg-slate-800 text-gray-400 px-1.5 py-0.5 rounded font-mono font-bold flex-shrink-0">${isSub ? '•' : '#'+(index+1)}${execDot}</span>
                <div class="min-w-0">
                    <p class="font-semibold text-slate-800 dark:text-slate-200 truncate leading-tight">${node.label}</p>
                    <p class="text-[9px] uppercase tracking-wider font-bold opacity-40 mt-0.5">${typeCategory}</p>
                </div>
            </div>
            <button onclick="event.stopPropagation();removeNode('${node.id}')" aria-label="Supprimer"
                    class="text-[10px] text-slate-400 hover:text-red-500 font-bold px-1.5 py-0.5 rounded hover:bg-red-50 dark:hover:bg-red-950/20 transition flex-shrink-0">✕</button>
        </div>
        ${summary ? `<p class="text-[10px] text-gray-400 dark:text-slate-500 truncate pl-7">${summary}</p>` : ''}
    `;
    block.addEventListener('click', () => selectNode(node.id));

    wrapper.appendChild(block);

    if (node.type === 'control_condition') {
        const grid = document.createElement('div');
        grid.className = 'grid grid-cols-2 gap-4 w-full max-w-3xl mt-4 border-t border-dashed border-slate-200 dark:border-slate-800 pt-4';

        ['trueBranch','falseBranch'].forEach((branch, bi) => {
            const col = document.createElement('div');
            col.className = 'flex flex-col items-center min-w-0';
            const badge = bi === 0
                ? '<span class="mb-2 bg-emerald-500 text-white font-extrabold text-[9px] px-2 py-0.5 rounded-full uppercase tracking-wider">Si Vrai</span>'
                : '<span class="mb-2 bg-red-500 text-white font-extrabold text-[9px] px-2 py-0.5 rounded-full uppercase tracking-wider">Si Faux</span>';
            col.innerHTML = badge;
            const nodes = Array.isArray(node[branch]) ? node[branch] : [];
            nodes.forEach((sub, i) => { if (i > 0) injectArrow(col); col.appendChild(buildNodeHTML(sub, i, true)); });
            if (nodes.length > 0) injectArrow(col);
            col.appendChild(makeBranchZone(node.id, branch));
            grid.appendChild(col);
        });
        wrapper.appendChild(grid);
    }
    return wrapper;
}

function makeBranchZone(parentId, branch) {
    const zone = document.createElement('div');
    zone.className = 'branch-zone w-full max-w-[180px] p-2.5 border border-dashed border-slate-300 dark:border-slate-700 text-slate-400 text-[10px] font-bold rounded-lg text-center cursor-pointer hover:border-indigo-500 hover:text-indigo-500 transition';
    zone.textContent = '+ Ajouter';
    zone.setAttribute('tabindex','0');
    zone.ondragover  = e => { e.preventDefault(); zone.classList.add('border-indigo-500','bg-indigo-50/50'); };
    zone.ondragleave = ()  => zone.classList.remove('border-indigo-500','bg-indigo-50/50');
    zone.ondrop      = e  => dropBranch(e, parentId, branch);
    zone.onclick     = e  => openCtxMenu(e, parentId, branch);
    zone.onkeydown   = e  => { if (e.key==='Enter'||e.key===' ') openCtxMenu(e, parentId, branch); };
    return zone;
}

// ── Canvas render ─────────────────────────────────────────────────────────────
function renderNodes() {
    const canvas  = document.getElementById('canvas');
    const emptyEl = document.getElementById('empty-msg');
    canvas.innerHTML = '';
    canvas.appendChild(emptyEl);

    const triggers       = window.nodesList.filter(n => n.type.startsWith('trigger_'));
    const actions        = window.nodesList.filter(n => !n.type.startsWith('trigger_'));
    const hasNodes       = window.nodesList.length > 0;
    const hasScheduler   = triggers.some(n => n.type === 'trigger_scheduler');
    const hasMainTrigger = triggers.some(n => n.type !== 'trigger_scheduler');

    // Palette lock: scheduler only locks scheduler; webhook/form lock each other
    ['trigger_webhook','trigger_scheduler','trigger_form'].forEach(t => {
        const b = document.getElementById('btn-'+t);
        if (!b) return;
        let locked;
        if (!hasNodes)                      locked = false;
        else if (t === 'trigger_scheduler') locked = hasScheduler;
        else                                locked = hasMainTrigger;
        if (locked) { b.setAttribute('disabled',''); b.style.opacity='0.25'; b.style.cursor='not-allowed'; }
        else        { b.removeAttribute('disabled'); b.style.opacity='1';    b.style.cursor='grab'; }
    });
    ['control_condition','action_delay','action_email','action_log'].forEach(t => {
        const b = document.getElementById('btn-'+t);
        if (!b) return;
        if (!hasNodes) { b.setAttribute('disabled',''); b.style.opacity='0.25'; b.style.cursor='not-allowed'; }
        else           { b.removeAttribute('disabled'); b.style.opacity='1';    b.style.cursor='grab'; }
    });

    document.getElementById('webhook-info').classList.toggle('hidden', !triggers.some(n => n.type==='trigger_webhook'));

    if (!hasNodes) {
        emptyEl.classList.remove('hidden');
        window.dispatchEvent(new CustomEvent('nodes-updated'));
        return;
    }
    emptyEl.classList.add('hidden');

    // Single vertical chain: all nodes top-to-bottom with arrows
    window.nodesList.forEach((node, i) => {
        if (i > 0) injectArrow(canvas);
        canvas.appendChild(buildNodeHTML(node, i, false));
    });

    window.dispatchEvent(new CustomEvent('nodes-updated'));
}

// ── Alpine panel component ────────────────────────────────────────────────────
function editorPanel() {
    return {
        tab: 'test',
        selectedNodeId: null,
        _nodesVersion: 0,
        editPayload: {},

        // Test state
        webhookPayload: '{"email":"client@exemple.com","nom":"Marie","montant":250,"produit":"Premium"}',
        formFields: {},
        testRunning: false,
        testResult: null,

        // Scheduler state
        schedulerType: 'daily',
        schedulerMinutes: 5,
        schedulerHourMinute: 0,
        schedulerTime: '09:00',
        schedulerDay: 1,
        schedulerNextRun: null,
        schedulerLoading: false,

        init() {
            window.addEventListener('nodes-updated', () => { this._nodesVersion++; });
            window.addEventListener('node-selected', e => {
                this.selectedNodeId = e.detail.nodeId;
                this.tab = 'config';
                const node = findNodeInTree(window.nodesList, e.detail.nodeId);
                this.editPayload = { ...(node?.payload || {}) };
                if (node?.type === 'trigger_scheduler') this.loadSchedulerFromPayload();
            });
            window.addEventListener('node-deselected', () => {
                this.selectedNodeId = null;
                this.editPayload = {};
            });
            this.$watch('tab', val => {
                if (val === 'test' && this.triggerNode?.type === 'trigger_scheduler') this.fetchNextRun();
            });
        },

        get selectedNode() {
            void this._nodesVersion;
            return this.selectedNodeId ? findNodeInTree(window.nodesList, this.selectedNodeId) : null;
        },

        get triggerNodes() {
            void this._nodesVersion;
            return window.nodesList.filter(n => n.type.startsWith('trigger_'));
        },

        // The "input trigger" for the test panel: webhook/form preferred over scheduler
        // (scheduler always fires automatically; user only needs to provide data for webhook/form)
        get triggerNode() {
            void this._nodesVersion;
            const nodes = window.nodesList.filter(n => n.type.startsWith('trigger_'));
            if (!nodes.length) return null;
            return nodes.find(n => n.type !== 'trigger_scheduler') || nodes[0];
        },

        get hasSchedulerInChain() {
            void this._nodesVersion;
            return window.nodesList.some(n => n.type === 'trigger_scheduler');
        },

        get declaredFields() {
            void this._nodesVersion;
            const formTrigger = window.nodesList.find(n => n.type === 'trigger_form');
            if (!formTrigger) return [];
            return (formTrigger.payload?.expected_fields || '').split(',').map(f => f.trim()).filter(Boolean);
        },

        get webhookDeclaredFields() {
            void this._nodesVersion;
            const webhookTrigger = window.nodesList.find(n => n.type === 'trigger_webhook');
            if (!webhookTrigger) return [];
            return (webhookTrigger.payload?.expected_fields || '').split(',').map(f => f.trim()).filter(Boolean);
        },

        // Aggregates vars from ALL triggers in the chain
        get availableVars() {
            void this._nodesVersion;
            const vars = [];
            window.nodesList.filter(n => n.type.startsWith('trigger_')).forEach(t => {
                if (t.type === 'trigger_scheduler') {
                    vars.push('triggered_at', 'scheduled_time');
                } else if (t.type === 'trigger_form') {
                    (t.payload?.expected_fields || '').split(',').map(f => f.trim()).filter(Boolean).forEach(f => vars.push(f));
                } else if (t.type === 'trigger_webhook') {
                    (t.payload?.expected_fields || '').split(',').map(f => f.trim()).filter(Boolean).forEach(f => vars.push(f));
                }
            });
            return [...new Set(vars)];
        },

        updatePayload(key, value) {
            this.editPayload[key] = value;
            if (this.selectedNodeId) updateNodePayload(this.selectedNodeId, key, value);
            // Re-read to keep in sync
            const node = findNodeInTree(window.nodesList, this.selectedNodeId);
            if (node) this.editPayload = { ...node.payload };
            // Notify Alpine getters (availableVars, webhookDeclaredFields, etc.) to recompute
            window.dispatchEvent(new CustomEvent('nodes-updated'));
        },

        insertIntoField(elId, payloadKey, varName) {
            const el = document.getElementById(elId);
            if (!el) return;
            const ins   = '{' + '{ ' + varName + ' }' + '}';
            const start = el.selectionStart ?? el.value.length;
            const end   = el.selectionEnd   ?? el.value.length;
            const val   = el.value.substring(0, start) + ins + el.value.substring(end);
            el.value    = val;
            this.editPayload[payloadKey] = val;
            updateNodePayload(this.selectedNodeId, payloadKey, val);
            el.focus();
            el.setSelectionRange(start + ins.length, start + ins.length);
        },

        nodeTypeLabel() {
            const map = {
                'trigger_webhook':'Déclencheur','trigger_form':'Déclencheur','trigger_scheduler':'Déclencheur',
                'control_condition':'Logique','action_delay':'Action','action_email':'Action','action_log':'Action',
            };
            return map[this.selectedNode?.type] || 'Nœud';
        },

        nodeTypeBadgeClass() {
            const t = this.selectedNode?.type || '';
            if (t.startsWith('trigger'))           return 'bg-indigo-100 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400';
            if (t === 'control_condition')          return 'bg-purple-100 dark:bg-purple-950/40 text-purple-700 dark:text-purple-400';
            if (t === 'action_delay')               return 'bg-amber-100 dark:bg-amber-950/40 text-amber-700 dark:text-amber-400';
            if (t === 'action_email')               return 'bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400';
            return 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400';
        },

        operatorLabel() {
            const labels = { equals:'==', not_equals:'!=', greater_than:'>', greater_or_equal:'>=', less_than:'<', less_or_equal:'<=', contains:'contient' };
            return labels[this.editPayload.operator] || this.editPayload.operator || '';
        },

        nodeLabel(nodeId) {
            const node = findNodeInTree(window.nodesList, nodeId);
            return node?.label || nodeId;
        },

        // ── Scheduler helpers ──────────────────────────────────────────────────
        computeCron() {
            const [h, m] = (this.schedulerTime || '09:00').split(':').map(Number);
            switch (this.schedulerType) {
                case 'minutely': return `*/${Math.max(1, this.schedulerMinutes)} * * * *`;
                case 'hourly':   return `${Math.max(0, this.schedulerHourMinute)} * * * *`;
                case 'daily':    return `${m} ${h} * * *`;
                case 'weekly':   return `${m} ${h} * * ${this.schedulerDay}`;
                case 'custom':   return this.editPayload.cron_expression || '';
            }
            return '';
        },

        applyScheduler() {
            const cron = this.computeCron();
            if (cron && this.schedulerType !== 'custom') this.updatePayload('cron_expression', cron);
        },

        handleSchedulerTypeChange(type) {
            this.schedulerType = type;
            this.applyScheduler();
        },

        applyScenario(scenario) {
            if (scenario === 'demo_1min')   { this.schedulerType = 'minutely'; this.schedulerMinutes = 1; }
            if (scenario === 'demo_daily')  { this.schedulerType = 'daily';    this.schedulerTime = '09:00'; }
            if (scenario === 'demo_monday') { this.schedulerType = 'weekly';   this.schedulerDay = 1; this.schedulerTime = '08:00'; }
            this.applyScheduler();
        },

        loadSchedulerFromPayload() {
            const cron = this.editPayload.cron_expression || '';
            const parts = cron.trim().split(/\s+/);
            if (parts.length !== 5) { this.schedulerType = cron ? 'custom' : 'daily'; return; }
            const [min, hour, dom, month, dow] = parts;
            if (/^\*\/\d+$/.test(min) && hour==='*' && dom==='*' && month==='*' && dow==='*') {
                this.schedulerType = 'minutely'; this.schedulerMinutes = parseInt(min.slice(2)) || 5;
            } else if (/^\d+$/.test(min) && hour==='*' && dom==='*' && month==='*' && dow==='*') {
                this.schedulerType = 'hourly'; this.schedulerHourMinute = parseInt(min);
            } else if (/^\d+$/.test(min) && /^\d+$/.test(hour) && dom==='*' && month==='*' && dow==='*') {
                this.schedulerType = 'daily';
                this.schedulerTime = `${String(hour).padStart(2,'0')}:${String(min).padStart(2,'0')}`;
            } else if (/^\d+$/.test(min) && /^\d+$/.test(hour) && dom==='*' && month==='*' && /^\d+$/.test(dow)) {
                this.schedulerType = 'weekly';
                this.schedulerTime = `${String(hour).padStart(2,'0')}:${String(min).padStart(2,'0')}`;
                this.schedulerDay = parseInt(dow);
            } else {
                this.schedulerType = 'custom';
            }
        },

        schedulerHumanLabel() {
            const [h, m] = (this.schedulerTime || '09:00').split(':').map(Number);
            const pad = n => String(n).padStart(2,'0');
            const days = ['dimanche','lundi','mardi','mercredi','jeudi','vendredi','samedi'];
            switch (this.schedulerType) {
                case 'minutely': return `Toutes les ${this.schedulerMinutes} minute${this.schedulerMinutes > 1 ? 's' : ''}`;
                case 'hourly':   return `Toutes les heures à HH:${pad(this.schedulerHourMinute)}`;
                case 'daily':    return `Chaque jour à ${pad(h)}:${pad(m)}`;
                case 'weekly':   return `Chaque ${days[this.schedulerDay] || 'lundi'} à ${pad(h)}:${pad(m)}`;
                case 'custom':   return this.editPayload.cron_expression ? `Expression : ${this.editPayload.cron_expression}` : 'Non configuré';
            }
            return '';
        },

        async fetchNextRun() {
            this.schedulerLoading = true;
            try {
                this.schedulerNextRun = await api('/api/workflows/' + WORKFLOW_ID + '/next-run');
            } catch(e) {
                this.schedulerNextRun = { error: e.message || 'Erreur lors du calcul du prochain déclenchement' };
            }
            this.schedulerLoading = false;
        },

        async runTest() {
            this.testRunning = true;
            this.testResult  = null;
            window.lastExecPath = {};

            let payload = {};
            const tn = this.triggerNode;

            try {
                if (tn?.type === 'trigger_form') {
                    payload = { ...this.formFields };
                } else if (tn?.type === 'trigger_webhook') {
                    try { payload = JSON.parse(this.webhookPayload || '{}'); } catch { payload = {}; }
                }
                // scheduler only: payload stays {} — startNodeId = scheduler, runs from there

                const result = await api('/api/workflows/' + WORKFLOW_ID + '/test', {
                    method: 'POST',
                    // No trigger_type: runner uses startNodeId (always the first node in chain)
                    // If scheduler is first, it runs first, then webhook processes the payload
                    body: JSON.stringify({ payload }),
                });

                this.testResult = result;
                colorCanvas(result.result?.path || {});

            } catch(e) {
                this.testResult = { success: false, result: { error: e.message || 'Erreur inattendue' } };
            }

            this.testRunning = false;
        },
    };
}

// ── Bootstrap ─────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    renderNodes();
    setSaveStatus(window.nodesList.length > 0 ? 'Flux chargé' : 'Prêt', 'indigo');
});
</script>
</x-app-layout>
