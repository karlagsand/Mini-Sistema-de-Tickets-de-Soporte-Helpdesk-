<x-guest-layout>
    <div class="auth-shell">
        <div class="auth-card">
            <div class="auth-brand">
                <h1 class="auth-title">Verificar correo electrónico</h1>
                <p class="auth-subtitle">
                    Antes de continuar, revisa tu bandeja de entrada y confirma tu dirección de correo electrónico.
                </p>
            </div>

            @if (session('status') == 'verification-link-sent')
                <div class="flash-success mb-5">
                    Se ha enviado un nuevo enlace de verificación a la dirección de correo registrada en tu cuenta.
                </div>
            @endif

            <div class="space-y-4">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf

                    <button type="submit" class="app-btn-primary w-full">
                        Reenviar correo de verificación
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit" class="app-btn-secondary w-full">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>