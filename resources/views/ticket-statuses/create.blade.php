<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="page-title">Crear estado</h1>
                <p class="page-subtitle">Alta de estados para el flujo de operación de tickets.</p>
            </div>

            <a href="{{ route('ticket-statuses.index') }}" class="app-btn-secondary">Volver al listado</a>
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
                <form method="POST" action="{{ route('ticket-statuses.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="name" class="form-label">Nombre</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <label for="slug" class="form-label">Slug</label>
                        <input id="slug" name="slug" type="text" value="{{ old('slug') }}" required>
                        <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                    </div>

                    <div>
                        <label for="sort_order" class="form-label">Orden</label>
                        <input id="sort_order" name="sort_order" type="number" min="1" max="100" value="{{ old('sort_order', 1) }}" required>
                        <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-3">
                        <input id="is_closed" name="is_closed" type="checkbox" value="1" class="!w-5 !h-5" @checked(old('is_closed'))>
                        <label for="is_closed" class="text-sm text-[var(--text-main)] font-medium">Marca este estado como de cierre</label>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('ticket-statuses.index') }}" class="app-btn-secondary">Cancelar</a>
                        <button type="submit" class="app-btn-primary">Guardar estado</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>