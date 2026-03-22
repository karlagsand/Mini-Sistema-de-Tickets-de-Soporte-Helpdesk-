<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Crear categoría</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <form method="POST" action="{{ route('categories.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Nombre" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="description" value="Descripción" />
                        <textarea id="description" name="description" rows="4"
                                  class="mt-1 block w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" checked>
                        <label for="is_active" class="text-sm text-slate-700">Categoría activa</label>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('categories.index') }}"
                           class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 transition">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-500 transition">
                            Guardar categoría
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>