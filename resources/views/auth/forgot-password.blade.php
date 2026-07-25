<x-guest-layout>
    <div class="auth-shell">
        <div class="auth-card">
            <div class="auth-brand">
                <h1 class="auth-title">Recuperar contraseña</h1>
                <p class="auth-subtitle">
                    Indica tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.
                </p>
            </div>

            <x-auth-session-status class="flash-success mb-5" :status="session('status')" />

            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf

                <div>
                    <x-input-label for="email" :value="__('Correo electrónico')" />
                    <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <button type="submit" class="app-btn-primary w-full">
                    Enviar enlace de recuperación
                </button>

                <a class="block text-center text-sm font-medium text-[var(--primary)] hover:text-[var(--primary-hover)]" href="{{ route('login') }}">
                    Volver al inicio de sesión
                </a>
            </form>
        </div>
    </div>
</x-guest-layout>