<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="page-title">Usuarios</h1>
                <p class="page-subtitle">Administra cuentas, roles y nivel de atención por jerarquía.</p>
            </div>

            <a href="{{ route('users.create') }}" class="app-btn-primary">Nuevo usuario</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-wrap space-y-6">
            @if(session('success'))
                <div class="flash-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="flash-error">{{ session('error') }}</div>
            @endif

            <section class="app-card p-0 overflow-hidden">
                <div class="border-b border-[var(--border-soft)] px-6 py-5">
                    <h2 class="section-title">Listado de usuarios</h2>
                    <p class="text-soft text-sm">Los usuarios con mayor nivel de atención aparecen primero para facilitar su revisión.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-[var(--border-soft)] bg-slate-50 text-left text-[var(--text-soft)]">
                                <th class="px-6 py-4 font-semibold">Nombre</th>
                                <th class="px-6 py-4 font-semibold">Correo</th>
                                <th class="px-6 py-4 font-semibold">Rol</th>
                                <th class="px-6 py-4 font-semibold">Nivel de atención</th>
                                <th class="px-6 py-4 font-semibold">Peso</th>
                                <th class="px-6 py-4 font-semibold text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr class="border-b border-[var(--border-soft)] transition hover:bg-slate-50">
                                    <td class="px-6 py-4 font-semibold text-[var(--text-main)]">{{ $user->name }}</td>
                                    <td class="px-6 py-4 text-soft">{{ $user->email }}</td>
                                    <td class="px-6 py-4">
                                        <span class="app-badge badge-blue">{{ $user->role->name ?? 'Sin rol' }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $attentionClass = match($user->position_level ?? 'operativo') {
                                                'director_general' => 'badge-rose',
                                                'subdirector' => 'badge-amber',
                                                'gerente' => 'badge-violet',
                                                default => 'badge-slate',
                                            };
                                        @endphp
                                        <span class="app-badge {{ $attentionClass }}">{{ $user->attentionLabel() }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-soft">{{ $user->attention_weight ?? 20 }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('users.show', $user) }}" class="app-btn-secondary">Ver</a>
                                            <a href="{{ route('users.edit', $user) }}" class="app-btn-warning">Editar</a>
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('¿Eliminar este usuario?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="app-btn-danger">Eliminar</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-soft">No hay usuarios registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="pagination-wrap px-6 py-4">
                    {{ $users->links() }}
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
