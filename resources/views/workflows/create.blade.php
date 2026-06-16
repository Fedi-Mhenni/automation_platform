<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 class="text-base font-semibold text-gray-900 dark:text-white">Nouveau workflow</h1>
        </div>
    </x-slot>

    <div class="p-6 max-w-lg">
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Créer un workflow</h2>
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-5">Donnez un nom à votre workflow. Vous configurerez les nœuds dans l'éditeur.</p>

            <form method="POST" action="{{ route('workflows.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1.5">
                        Nom du workflow <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Ex: Notification de formulaire contact"
                        required
                        autofocus
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-700 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                    />
                    @error('name')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        Créer et ouvrir l'éditeur
                    </button>
                    <a href="{{ route('dashboard') }}" class="text-xs text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200 transition">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
