<x-guest-layout>
    <div class="login-shell">
        <section class="login-hero" aria-label="Información de la mesa de ayuda">
            <div class="login-hero-decoration login-hero-decoration-one"></div>
            <div class="login-hero-decoration login-hero-decoration-two"></div>
            <div class="login-hero-dot-grid" aria-hidden="true"></div>

            <div class="login-hero-content">
                <div class="login-hero-brand">
                    <div class="login-hero-logo">
                        <x-application-logo class="h-10 w-auto" />
                    </div>
                    <span>Mesa de ayuda</span>
                </div>

                <div>
                    <p class="login-eyebrow">Soporte tecnológico empresarial</p>
                    <h1 class="login-hero-title">Mesa de ayuda empresarial</h1>
                    <p class="login-hero-subtitle">
                        Reporta problemas tecnológicos, consulta avances y da seguimiento a tus solicitudes de atención en un solo lugar.
                    </p>
                </div>

                <div class="login-feature-grid">
                    <div class="login-feature-card">
                        <span class="login-feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M8 12.5l2.6 2.6L16.5 8.8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" />
                            </svg>
                        </span>
                        <div>
                            <strong>Registro claro</strong>
                            <p>Envía tu solicitud con la información necesaria.</p>
                        </div>
                    </div>

                    <div class="login-feature-card">
                        <span class="login-feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M4 5h16M4 12h10M4 19h7" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                <path d="M17 14l2 2 3-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <div>
                            <strong>Seguimiento ordenado</strong>
                            <p>Consulta avances y actualizaciones del caso.</p>
                        </div>
                    </div>

                    <div class="login-feature-card">
                        <span class="login-feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 22s7-3.5 7-10V5l-7-3-7 3v7c0 6.5 7 10 7 10z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
                                <path d="M9 12l2 2 4-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <div>
                            <strong>Acceso seguro</strong>
                            <p>Ingresa con tus credenciales autorizadas.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="login-form-panel" aria-label="Formulario de inicio de sesión">
            <div class="login-form-card">
                <div class="login-form-brand">
                    <div class="login-form-logo">
                        <x-application-logo class="h-20 w-auto" />
                    </div>
                    <h2>Iniciar sesión</h2>
                    <p>Accede para consultar o gestionar solicitudes dentro de la mesa de ayuda.</p>
                </div>

                <x-auth-session-status class="flash-success mb-5" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <x-input-label for="email" :value="__('Correo electrónico')" />
                        <div class="login-input-wrap mt-1">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M4 6h16v12H4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
                                    <path d="M4 7l8 6 8-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <x-text-input id="email" class="login-input block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="nombre@empresa.com" />
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" :value="__('Contraseña')" />
                        <div class="login-input-wrap mt-1">
                            <span class="login-input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M7 10V8a5 5 0 0110 0v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    <path d="M6 10h12v10H6z" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
                                    <path d="M12 14v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                </svg>
                            </span>
                            <x-text-input id="password" class="login-input block w-full"
                                          type="password"
                                          name="password"
                                          required autocomplete="current-password"
                                          placeholder="Ingresa tu contraseña" />
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="login-form-actions-row">
                        <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-[var(--text-soft)]">
                            <input id="remember_me" type="checkbox" class="rounded border-[var(--border-strong)] text-[var(--primary)] shadow-sm focus:ring-[var(--primary)]" name="remember">
                            <span>Recordarme</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a class="login-secondary-link" href="{{ route('password.request') }}">
                                ¿Olvidaste tu contraseña?
                            </a>
                        @endif
                    </div>

                    <button type="submit" class="login-submit-btn">
                        Entrar
                    </button>
                </form>

                <div class="login-help-note">
                    <span aria-hidden="true">?</span>
                    <p>Usa las credenciales asignadas por tu organización. Si no puedes ingresar, solicita apoyo al área correspondiente.</p>
                </div>
            </div>
        </section>
    </div>
</x-guest-layout>
