<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="page-title">Detalle de estado</h1>
                <p class="page-subtitle">Consulta de atributos del estado seleccionado.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('ticket-statuses.index') }}" class="app-btn-secondary">Volver</a>
                <a href="{{ route('ticket-statuses.edit', $status) }}" class="app-btn-warning">Editar</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-wrap">
            <section class="app-card p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="info-panel">
                        <p class="info-label">Nombre</p>
                        <p class="info-value">{{ $status->name }}</p>
                    </div>

                    <div class="info-panel">
                        <p class="info-label">Slug</p>
                        <p class="info-value">{{ $status->slug }}</p>
                    </div>

                    <div class="info-panel">
                        <p class="info-label">Orden</p>
                        <p class="info-value">{{ $status->sort_order }}</p>
                    </div>

                    <div class="info-panel">
                        <p class="info-label">Cierra ticket</p>
                        <p class="info-value">{{ $status->is_closed ? 'Sí' : 'No' }}</p>
                    </div>

                    <div class="info-panel">
                        <p class="info-label">Fecha de creación</p>
                        <p class="info-value">{{ $status->created_at?->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>