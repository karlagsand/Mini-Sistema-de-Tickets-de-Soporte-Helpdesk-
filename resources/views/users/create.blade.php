<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="page-title">Crear usuario</h1>
                <p class="page-subtitle">Registra un usuario y define su rol operativo y nivel de atención.</p>
            </div>

            <a href="{{ route('users.index') }}" class="app-btn-secondary">Volver al listado</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-wrap space-y-6">
            @if ($errors->any())
                <div class="flash-error" role="alert" aria-live="polite">
                    <p class="font-semibold mb-2">Se encontraron errores:</p>
                    <ul class="list-disc pl-5 text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 app-card p-6">
                    <div class="mb-6">
                        <h2 class="section-title">Formulario de registro</h2>
                        <p class="text-soft text-sm">El nivel de atención se usa para ordenar tickets de acuerdo con la jerarquía del solicitante.</p>
                    </div>

                    <form method="POST" action="{{ route('users.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <label for="name" class="form-label">Nombre</label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" required>
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="role_id" class="form-label">Rol del sistema</label>
                                <select id="role_id" name="role_id" required>
                                    <option value="">Seleccione un rol</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="form-help">Controla permisos: Usuario, Agente o Administrador.</p>
                                <x-input-error :messages="$errors->get('role_id')" class="mt-2" />
                            </div>

                            <div>
                                <label for="position_level" class="form-label">Nivel de atención</label>
                                <select id="position_level" name="position_level" required>
                                    @foreach($attentionLevels as $key => $level)
                                        <option value="{{ $key }}" @selected(old('position_level', 'operativo') === $key)>
                                            {{ $level['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="form-help">Ayuda a priorizar tickets de altos mandos sin que el usuario elija la urgencia.</p>
                                <x-input-error :messages="$errors->get('position_level')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="password" class="form-label">Contraseña</label>
                                <input id="password" name="password" type="password" required>
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <div>
                                <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                                <input id="password_confirmation" name="password_confirmation" type="password" required>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <a href="{{ route('users.index') }}" class="app-btn-secondary">Cancelar</a>
                            <button type="submit" class="app-btn-primary">Guardar usuario</button>
                        </div>
                    </form>
                </div>

                <div class="app-card p-6">
                    <h2 class="section-title">Criterio recomendado</h2>
                    <div class="space-y-4 text-sm text-soft">
                        <div class="info-panel">
                            <p class="info-label">Director General</p>
                            <p class="info-value">Atención preferente máxima.</p>
                        </div>
                        <div class="info-panel">
                            <p class="info-label">Subdirector / Gerente</p>
                            <p class="info-value">Atención preferente media-alta.</p>
                        </div>
                        <div class="info-panel">
                            <p class="info-label">Operativo</p>
                            <p class="info-value">Atención regular según impacto, urgencia y SLA.</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
