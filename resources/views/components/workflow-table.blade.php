@props(['workflows'])

<div class="p-6 bg-white shadow sm:rounded-lg">
    <h3 class="text-lg font-medium text-gray-900 mb-4">
        Mes scénarios actifs
    </h3>
    
    @if($workflows->isEmpty())
        <p class="text-gray-500">Vous n'avez pas encore créé de workflow.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">Nom</th>
                        <th scope="col" class="px-6 py-3">Token</th>
                        <th scope="col" class="px-6 py-3">Statut</th>
                        <th scope="col" class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($workflows as $workflow)
                        <tr class="bg-white border-b">
                            <td class="px-6 py-4 font-medium text-gray-900">
                                <a href="{{ route('workflows.show', $workflow) }}" class="text-indigo-600 hover:underline font-bold">
                                    {{ $workflow->name }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs text-gray-700">
                                        {{ $workflow->token }}
                                    </span>
                                    <button type="button" onclick="copyWorkflowToken('{{ $workflow->token }}')" class="text-xs text-indigo-600 hover:underline">
                                        Copier
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold tracking-wide ring-1 {{ $workflow->is_active ? 'bg-emerald-950/20 text-emerald-800 ring-emerald-300' : 'bg-slate-100 text-slate-600 ring-slate-200' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $workflow->is_active ? 'bg-emerald-700' : 'bg-slate-400' }}"></span>
                                    {{ $workflow->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <form action="{{ route('workflows.destroy', $workflow) }}" method="POST" onsubmit="return confirm('Supprimer ce workflow ?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 font-semibold">
                                        Supprimer
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<script>
    function copyWorkflowToken(token) {
        if (!token) return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(token);
            return;
        }
        const input = document.createElement('input');
        input.value = token;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
    }
</script>
