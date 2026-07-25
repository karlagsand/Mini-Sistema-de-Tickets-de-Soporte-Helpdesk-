<section aria-labelledby="update-password-heading">
    <header>
        <h2 id="update-password-heading" class="section-title mb-1">
            Actualizar contraseña
        </h2>

        <p class="text-soft text-sm">
            Usa una contraseña robusta para proteger tu cuenta y reducir riesgos de acceso no autorizado.
        </p>
    </header>

    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label for="update_password_current_password" class="form-label">Contraseña actual</label>
            <input
                id="update_password_current_password"
                name="current_password"
                type="password"
                autocomplete="current-password"
            >
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <label for="update_password_password" class="form-label">Nueva contraseña</label>
            <input
                id="update_password_password"
                name="password"
                type="password"
                autocomplete="new-password"
            >
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <label for="update_password_password_confirmation" class="form-label">Confirmar nueva contraseña</label>
            <input
                id="update_password_password_confirmation"
                name="password_confirmation"
                type="password"
                autocomplete="new-password"
            >
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="rounded-2xl border border-[var(--border-soft)] bg-[var(--bg-card-soft)] p-4 text-sm text-soft">
            Recomendación: utiliza una contraseña larga, difícil de adivinar y distinta a la de otros servicios.
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <button type="submit" class="app-btn-warning">
                Actualizar contraseña
            </button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-[var(--success-text)]"
                >
                    Contraseña actualizada correctamente.
                </p>
            @endif
        </div>
    </form>
</section>