<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="page-title">Detalle de usuario</h1>
                <p class="page-subtitle">Consulta el rol y nivel de atención asignado.</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('users.edit', $user) }}" class="app-btn-warning">Editar</a>
                <a href="{{ route('users.index') }}" class="app-btn-secondary">Volver</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-wrap">
            <section class="app-card p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="info-panel">
                    <p class="info-label">Nombre</p>
                    <p class="info-value">{{ $user->name }}</p>
                </div>
                <div class="info-panel">
                    <p class="info-label">Correo</p>
                    <p class="info-value">{{ $user->email }}</p>
                </div>
                <div class="info-panel">
                    <p class="info-label">Rol</p>
                    <p class="info-value">{{ $user->role->name ?? 'Sin rol' }}</p>
                </div>
                <div class="info-panel">
                    <p class="info-label">Nivel de atención</p>
                    <p class="info-value">{{ $user->attentionLabel() }}</p>
                    <p class="info-subvalue">Peso interno: {{ $user->attention_weight ?? 20 }}</p>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
