<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Crear prioridad</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <form method="POST" action="{{ route('priorities.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Nombre" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="level" value="Nivel" />
                        <x-text-input id="level" name="level" type="number" min="1" max="10" class="mt-1 block w-full" :value="old('level')" required />
                        <x-input-error :messages="$errors->get('level')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="color" value="Color" />
                        <x-text-input id="color" name="color" type="text" class="mt-1 block w-full" :value="old('color')" />
                        <x-input-error :messages="$errors->get('color')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('priorities.index') }}"
                           class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 transition">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-500 transition">
                            Guardar prioridad
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>