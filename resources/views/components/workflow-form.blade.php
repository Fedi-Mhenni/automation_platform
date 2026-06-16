<div class="p-6 bg-white shadow sm:rounded-lg">
    <h3 class="text-lg font-medium text-gray-900 mb-4">
        Créer un nouveau Workflow
    </h3>
    <form action="{{ route('workflows.store') }}" method="POST" class="flex gap-4">
        @csrf
        <input type="text" name="name" placeholder="Nom du workflow..." required
            class="form-input rounded-md shadow-sm mt-1 block w-full bg-gray-50 text-gray-900 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition ease-in-out duration-150">
            Créer
        </button>
    </form>
</div>