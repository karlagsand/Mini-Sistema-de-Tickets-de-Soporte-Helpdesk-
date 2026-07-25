<section class="space-y-6" aria-labelledby="delete-account-heading">
    <header>
        <h2 id="delete-account-heading" class="text-lg font-semibold text-red-700">
            Eliminar cuenta
        </h2>

        <p class="text-sm text-red-700/90">
            Esta acción elimina permanentemente tu cuenta y todos los recursos asociados. Antes de continuar, asegúrate de no necesitar la información almacenada.
        </p>
    </header>

    <button
        type="button"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="app-btn-danger"
    >
        Eliminar cuenta
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="POST" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('DELETE')

            <h2 class="text-lg font-semibold text-[var(--text-main)]">
                ¿Seguro que deseas eliminar tu cuenta?
            </h2>

            <p class="mt-2 text-sm text-soft leading-6">
                Una vez eliminada, todos sus datos y recursos serán borrados de manera permanente. Ingresa tu contraseña para confirmar esta acción.
            </p>

            <div class="mt-6">
                <label for="password" class="form-label">Contraseña</label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    placeholder="Ingresa tu contraseña"
                    autocomplete="current-password"
                >

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    x-on:click="$dispatch('close')"
                    class="app-btn-secondary"
                >
                    Cancelar
                </button>

                <button type="submit" class="app-btn-danger">
                    Confirmar eliminación
                </button>
            </div>
        </form>
    </x-modal>
</section>