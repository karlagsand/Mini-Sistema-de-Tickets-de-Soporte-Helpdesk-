<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="page-title">Editar usuario</h1>
                <p class="page-subtitle">Actualiza datos generales, rol, nivel de atención y credenciales.</p>
            </div>

            <a href="{{ route('users.index') }}" class="app-btn-secondary">Volver al listado</a>
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

            <section class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <div class="xl:col-span-2 app-card p-6">
                    <div class="mb-6">
                        <h2 class="section-title">Formulario de edición</h2>
                        <p class="text-soft text-sm">El nivel de atención impacta el orden en que se visualizan y atienden los tickets.</p>
                    </div>

                    <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="name" class="form-label">Nombre</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required>
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="role_id" class="form-label">Rol</label>
                                <select id="role_id" name="role_id" required>
                                    <option value="">Seleccione un rol</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('role_id')" class="mt-2" />
                            </div>

                            <div>
                                <label for="position_level" class="form-label">Nivel de atención</label>
                                <select id="position_level" name="position_level" required>
                                    @foreach($attentionLevels as $key => $level)
                                        <option value="{{ $key }}" @selected(old('position_level', $user->position_level ?? 'operativo') === $key)>
                                            {{ $level['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('position_level')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="password" class="form-label">Nueva contraseña</label>
                                <input id="password" name="password" type="password">
                                <p class="form-help">Déjala en blanco si no deseas modificarla.</p>
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <div>
                                <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                                <input id="password_confirmation" name="password_confirmation" type="password">
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                            <a href="{{ route('users.index') }}" class="app-btn-secondary">Cancelar</a>
                            <button type="submit" class="app-btn-warning">Actualizar usuario</button>
                        </div>
                    </form>
                </div>

                <div class="app-card p-6">
                    <h2 class="section-title">Datos actuales</h2>
                    <div class="space-y-4">
                        <div class="info-panel">
                            <p class="info-label">Nombre actual</p>
                            <p class="info-value">{{ $user->name }}</p>
                        </div>
                        <div class="info-panel">
                            <p class="info-label">Rol actual</p>
                            <p class="info-value">{{ $user->role->name ?? 'Sin rol' }}</p>
                        </div>
                        <div class="info-panel">
                            <p class="info-label">Nivel actual</p>
                            <p class="info-value">{{ $user->attentionLabel() }}</p>
                            <p class="info-subvalue">Peso de atención: {{ $user->attention_weight ?? 20 }}</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
