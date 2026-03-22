<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Crear estado</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <form method="POST" action="{{ route('ticket-statuses.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Nombre" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="slug" value="Slug" />
                        <x-text-input id="slug" name="slug" type="text" class="mt-1 block w-full" :value="old('slug')" required />
                        <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="sort_order" value="Orden" />
                        <x-text-input id="sort_order" name="sort_order" type="number" min="1" max="100" class="mt-1 block w-full" :value="old('sort_order', 1)" required />
                        <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="is_closed" name="is_closed" type="checkbox" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <label for="is_closed" class="text-sm text-slate-700">Marca este estado como de cierre</label>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('ticket-statuses.index') }}"
                           class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 transition">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-500 transition">
                            Guardar estado
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>