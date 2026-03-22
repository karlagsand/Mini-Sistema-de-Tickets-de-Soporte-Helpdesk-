<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Crear ticket</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700">
                    <p class="font-semibold mb-2">Se encontraron errores:</p>
                    <ul class="list-disc pl-5 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <form method="POST" action="{{ route('tickets.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="subject" value="Asunto" />
                        <x-text-input
                            id="subject"
                            name="subject"
                            type="text"
                            class="mt-1 block w-full"
                            :value="old('subject')"
                            required
                        />
                        <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="category_id" value="Categoría" />
                        <select
                            id="category_id"
                            name="category_id"
                            class="mt-1 block w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                            required
                        >
                            <option value="">Seleccione una categoría</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="priority_id" value="Prioridad" />
                        <select
                            id="priority_id"
                            name="priority_id"
                            class="mt-1 block w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                            required
                        >
                            <option value="">Seleccione una prioridad</option>
                            @foreach($priorities as $priority)
                                <option value="{{ $priority->id }}" @selected(old('priority_id') == $priority->id)>
                                    {{ $priority->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('priority_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="description" value="Descripción" />
                        <textarea
                            id="description"
                            name="description"
                            rows="7"
                            class="mt-1 block w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500"
                            required
                        >{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <a
                            href="{{ route('tickets.index') }}"
                            class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 transition"
                        >
                            Cancelar
                        </a>

                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-500 transition"
                        >
                            Guardar ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>