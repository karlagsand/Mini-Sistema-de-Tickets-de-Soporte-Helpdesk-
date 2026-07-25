<x-guest-layout>
    <div class="auth-shell">
        <div class="auth-card">
            <div class="auth-brand">
                <h1 class="auth-title">Restablecer contraseña</h1>
                <p class="auth-subtitle">
                    Define una nueva contraseña para recuperar el acceso a tu cuenta.
                </p>
            </div>

            <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div>
                    <x-input-label for="email" :value="__('Correo electrónico')" />
                    <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" :value="__('Nueva contraseña')" />
                    <x-text-input id="password" class="mt-1 block w-full"
                                  type="password"
                                  name="password"
                                  required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirmar nueva contraseña')" />
                    <x-text-input id="password_confirmation" class="mt-1 block w-full"
                                  type="password"
                                  name="password_confirmation" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <button type="submit" class="app-btn-primary w-full">
                    Restablecer contraseña
                </button>
            </form>
        </div>
    </div>
</x-guest-layout>