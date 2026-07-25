<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="page-title">Detalle de categoría</h1>
                <p class="page-subtitle">Consulta de datos generales de la categoría.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('categories.index') }}" class="app-btn-secondary">Volver</a>
                <a href="{{ route('categories.edit', $category) }}" class="app-btn-warning">Editar</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-wrap">
            <section class="app-card p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="info-panel">
                        <p class="info-label">Nombre</p>
                        <p class="info-value">{{ $category->name }}</p>
                    </div>

                    <div class="info-panel">
                        <p class="info-label">Estado</p>
                        <p class="info-value">{{ $category->is_active ? 'Activa' : 'Inactiva' }}</p>
                    </div>

                    <div class="info-panel md:col-span-2">
                        <p class="info-label">Descripción</p>
                        <p class="info-value">{{ $category->description ?: 'Sin descripción' }}</p>
                    </div>

                    <div class="info-panel">
                        <p class="info-label">Fecha de creación</p>
                        <p class="info-value">{{ $category->created_at?->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>