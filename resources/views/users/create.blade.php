<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Crear usuario</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <form method="POST" action="{{ route('users.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Nombre" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" value="Correo electrónico" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="role_id" value="Rol" />
                        <select id="role_id" name="role_id"
                                class="mt-1 block w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="">Seleccione un rol</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('role_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" value="Contraseña" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" value="Confirmar contraseña" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('users.index') }}"
                           class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 transition">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="px-4 py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-500 transition">
                            Guardar usuario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>