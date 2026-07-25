<x-guest-layout>
    <div class="auth-shell">
        <div class="auth-card">
            <div class="auth-brand">
                <h1 class="auth-title">Crear cuenta</h1>
                <p class="auth-subtitle">
                    Registra una nueva cuenta para acceder al sistema y operar según el rol asignado.
                </p>
            </div>

            <form method="POST" action="{{ route('register') }}" class="space-y-5">
                @csrf

                <div>
                    <x-input-label for="name" :value="__('Nombre')" />
                    <x-text-input id="name" class="mt-1 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" :value="__('Correo electrónico')" />
                    <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" :value="__('Contraseña')" />
                    <x-text-input id="password" class="mt-1 block w-full"
                                  type="password"
                                  name="password"
                                  required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
                    <x-text-input id="password_confirmation" class="mt-1 block w-full"
                                  type="password"
                                  name="password_confirmation" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <div class="flex flex-col gap-3">
                    <button type="submit" class="app-btn-primary w-full">
                        Registrarse
                    </button>

                    <a class="text-sm font-medium text-center text-[var(--primary)] hover:text-[var(--primary-hover)]" href="{{ route('login') }}">
                        ¿Ya tienes cuenta? Inicia sesión
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>