<x-guest-layout>
    <div class="auth-shell">
        <div class="auth-card">
            <div class="auth-brand">
                <h1 class="auth-title">Confirmar contraseña</h1>
                <p class="auth-subtitle">
                    Por seguridad, confirma tu contraseña antes de continuar.
                </p>
            </div>

            <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
                @csrf

                <div>
                    <x-input-label for="password" :value="__('Contraseña')" />
                    <x-text-input id="password" class="mt-1 block w-full"
                                  type="password"
                                  name="password"
                                  required autocomplete="current-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <button type="submit" class="app-btn-primary w-full">
                    Confirmar
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>