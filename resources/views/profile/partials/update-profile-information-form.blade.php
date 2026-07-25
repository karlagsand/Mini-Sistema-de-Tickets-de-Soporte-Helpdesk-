<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="page-title">Perfil</h1>
                <p class="page-subtitle">
                    Administra la información de tu cuenta, tu contraseña y las opciones de seguridad.
                </p>
            </div>

            <a href="{{ route('dashboard') }}" class="app-btn-secondary">
                Volver al dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="page-wrap space-y-6">
            <section class="grid grid-cols-1 md:grid-cols-3 gap-6" aria-label="Resumen del perfil">
                <div class="metric-card">
                    <p class="metric-label">Nombre</p>
                    <p class="text-xl font-bold text-[var(--text-main)] mt-2 break-words">
                        {{ $user->name }}
                    </p>
                </div>

                <div class="metric-card">
                    <p class="metric-label">Correo</p>
                    <p class="text-base font-semibold text-[var(--text-main)] mt-2 break-words">
                        {{ $user->email }}
                    </p>
                </div>

                <div class="metric-card">
                    <p class="metric-label">Estado del correo</p>
                    <div class="mt-3">
                        @if($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail)
                            @if($user->hasVerifiedEmail())
                                <span class="app-badge badge-emerald">Verificado</span>
                            @else
                                <span class="app-badge badge-amber">Pendiente de verificación</span>
                            @endif
                        @else
                            <span class="app-badge badge-slate">No aplica</span>
                        @endif
                    </div>
                </div>
            </section>

            <section class="app-card p-6">
                <div class="max-w-3xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </section>

            <section class="app-card p-6">
                <div class="max-w-3xl">
                    @include('profile.partials.update-password-form')
                </div>
            </section>

            <section class="app-card p-6 border border-red-200 bg-red-50/60">
                <div class="max-w-3xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </section>
        </div>
    </div>
</x-app-layout>