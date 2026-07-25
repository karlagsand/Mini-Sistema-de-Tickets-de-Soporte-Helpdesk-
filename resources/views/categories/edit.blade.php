<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="page-title">Editar categoría</h1>
                <p class="page-subtitle">Actualización de datos de la categoría seleccionada.</p>
            </div>

            <a href="{{ route('categories.index') }}" class="app-btn-secondary">Volver al listado</a>
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
                <form method="POST" action="{{ route('categories.update', $category) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="form-label">Nombre</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $category->name) }}" required>
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <label for="description" class="form-label">Descripción</label>
                        <textarea id="description" name="description" rows="4">{{ old('description', $category->description) }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-3">
                        <input id="is_active" name="is_active" type="checkbox" value="1" class="!w-5 !h-5" @checked(old('is_active', $category->is_active))>
                        <label for="is_active" class="text-sm text-[var(--text-main)] font-medium">Categoría activa</label>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('categories.index') }}" class="app-btn-secondary">Cancelar</a>
                        <button type="submit" class="app-btn-warning">Actualizar categoría</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>