<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="page-title">Editar prioridad</h1>
                <p class="page-subtitle">Actualiza la configuración de la prioridad seleccionada.</p>
            </div>

            <a href="{{ route('priorities.index') }}" class="app-btn-secondary">Volver al listado</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-wrap space-y-6">
            @if ($errors->any())
                <div class="flash-error">
                    <p class="font-semibold mb-2">Se encontraron errores:</p>
                    <ul class="list-disc pl-5 text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="app-card p-6">
                <form method="POST" action="{{ route('priorities.update', $priority) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="form-label">Nombre</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $priority->name) }}" required>
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <label for="level" class="form-label">Nivel</label>
                        <input id="level" name="level" type="number" min="1" max="10" value="{{ old('level', $priority->level) }}" required>
                        <x-input-error :messages="$errors->get('level')" class="mt-2" />
                    </div>

                    <div>
                        <label for="color" class="form-label">Color</label>
                        <input id="color" name="color" type="text" value="{{ old('color', $priority->color) }}">
                        <x-input-error :messages="$errors->get('color')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('priorities.index') }}" class="app-btn-secondary">Cancelar</a>
                        <button type="submit" class="app-btn-warning">Actualizar prioridad</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>