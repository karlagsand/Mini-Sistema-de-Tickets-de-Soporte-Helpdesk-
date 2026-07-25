<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Helpdesk | Sistema de Mesa de Ayuda</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo-hd.png') }}?v=3">
    <link rel="shortcut icon" href="{{ asset('images/logo-hd.png') }}?v=3">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Figtree, ui-sans-serif, system-ui, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(109, 74, 255, 0.08), transparent 18%),
                radial-gradient(circle at bottom right, rgba(139, 92, 246, 0.08), transparent 20%),
                linear-gradient(180deg, #f7f8fc 0%, #eef2f9 100%);
            color: #172033;
        }

        .landing-wrap {
            width: min(1180px, calc(100% - 2rem));
            margin: 0 auto;
        }

        .landing-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.25rem 0;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 0.9rem;
            text-decoration: none;
            color: #172033;
        }

        .brand-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #ede9fe 0%, #e9d5ff 100%);
            border: 1px solid #c4b5fd;
            border-radius: 1rem;
            box-shadow: 0 12px 28px rgba(91, 62, 230, 0.10);
            overflow: hidden;
        }

        .welcome-logo {
            display: block;
            max-height: 34px;
            width: auto;
        }

        .brand-logo img {
            max-height: 34px;
            width: auto;
        }

        .brand-title {
            display: block;
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: 0.01em;
            color: #172033;
        }

        .brand-subtitle {
            display: block;
            font-size: 0.86rem;
            color: #64748b;
            margin-top: 0.15rem;
        }

        .landing-btn,
        .landing-btn-outline {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            border-radius: 999px;
            padding: 0.9rem 1.35rem;
            font-weight: 700;
            transition: 0.2s ease;
        }

        .landing-btn {
            background: linear-gradient(135deg, #6d4aff 0%, #8b5cf6 100%);
            color: #ffffff;
            border: 1px solid transparent;
            box-shadow: 0 14px 28px rgba(109, 74, 255, 0.18);
        }

        .landing-btn:hover {
            color: #ffffff;
            transform: translateY(-1px);
            filter: brightness(1.02);
        }

        .landing-btn-outline {
            background: #ffffff;
            color: #172033;
            border: 1px solid #dbe3f0;
        }

        .landing-btn-outline:hover {
            background: #f8fafc;
            color: #172033;
        }

        .hero {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 1.5rem;
            align-items: stretch;
            padding: 1rem 0 3rem;
        }

        .hero-main {
            background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
            border: 1px solid #dbe3f0;
            border-radius: 2rem;
            padding: 2rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.55rem 0.95rem;
            border-radius: 999px;
            background: #f3f0ff;
            border: 1px solid #ddd6fe;
            color: #4c1d95;
            font-size: 0.9rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .hero-title {
            margin: 0;
            font-size: clamp(2.2rem, 4vw, 4.2rem);
            line-height: 1.04;
            font-weight: 900;
            letter-spacing: -0.04em;
            color: #172033;
            max-width: 9.5ch;
        }

        .hero-title span {
            color: #4c1d95;
        }

        .hero-text {
            margin-top: 1rem;
            font-size: 1rem;
            line-height: 1.8;
            color: #52607a;
            max-width: 58ch;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.9rem;
            margin-top: 1.5rem;
        }

        .mini-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
            margin-top: 1.75rem;
        }

        .mini-stat {
            background: #f8faff;
            border: 1px solid #e2e8f0;
            border-radius: 1.2rem;
            padding: 1rem;
        }

        .mini-stat-label {
            font-size: 0.82rem;
            color: #64748b;
            margin-bottom: 0.35rem;
        }

        .mini-stat-value {
            font-size: 1.15rem;
            font-weight: 900;
            color: #172033;
        }

        .hero-side {
            display: grid;
            gap: 1rem;
        }

        .side-highlight {
            background: linear-gradient(135deg, #4c1d95 0%, #6d28d9 100%);
            color: #ffffff;
            border-radius: 2rem;
            padding: 1.8rem;
            box-shadow: 0 20px 40px rgba(76, 29, 149, 0.22);
        }

        .side-highlight h2 {
            margin: 0 0 0.65rem;
            font-size: 1.35rem;
            font-weight: 800;
            color: #ffffff;
        }

        .side-highlight p {
            margin: 0;
            font-size: 0.96rem;
            line-height: 1.75;
            color: rgba(255,255,255,0.86);
        }

        .feature-grid {
            display: grid;
            gap: 1rem;
        }

        .feature {
            background: #ffffff;
            border: 1px solid #dbe3f0;
            border-radius: 1.4rem;
            padding: 1.2rem;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
        }

        .feature-icon {
            width: 46px;
            height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            background: #f3f0ff;
            color: #4c1d95;
            margin-bottom: 0.8rem;
        }

        .feature h3 {
            margin: 0 0 0.45rem;
            font-size: 1rem;
            font-weight: 800;
            color: #172033;
        }

        .feature p {
            margin: 0;
            font-size: 0.92rem;
            line-height: 1.7;
            color: #52607a;
        }

        .feature svg {
            width: 22px;
            height: 22px;
        }

        @media (max-width: 980px) {
            .hero {
                grid-template-columns: 1fr;
            }

            .mini-stats {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .landing-nav {
                flex-direction: column;
                align-items: stretch;
            }

            .hero-actions {
                flex-direction: column;
            }

            .landing-btn,
            .landing-btn-outline {
                width: 100%;
            }

            .hero-main,
            .side-highlight,
            .feature {
                padding: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="landing-wrap">
        <header class="landing-nav">
            <a href="{{ url('/') }}" class="brand" aria-label="Inicio Helpdesk">
                <span class="brand-logo">
                    <img src="{{ asset('images/logo-hd.png') }}" alt="Logo Helpdesk" class="welcome-logo">
                </span>

                <span>
                    <span class="brand-title">Helpdesk</span>
                    <span class="brand-subtitle">Sistema de mesa de ayuda</span>
                </span>
            </a>

            @auth
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('dashboard') }}" class="landing-btn">
                        Ir al dashboard
                    </a>
                @else
                    <a href="{{ route('tickets.index') }}" class="landing-btn">
                        Entrar al sistema
                    </a>
                @endif
            @else
                <a href="{{ route('login') }}" class="landing-btn">
                    Iniciar sesión
                </a>
            @endauth
        </header>

        <main class="hero">
            <section class="hero-main" aria-label="Presentación principal">
                <div class="hero-badge">
                    Soporte, seguimiento y control
                </div>

                <h1 class="hero-title">
                    Gestiona tu <span>mesa de ayuda</span> con claridad.
                </h1>

                <p class="hero-text">
                    Registra tickets, asigna responsables, da seguimiento a incidencias y consulta métricas operativas desde una sola plataforma.
                </p>

                <div class="hero-actions">
                    @auth
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('dashboard') }}" class="landing-btn">
                                Entrar al sistema
                            </a>
                        @else
                            <a href="{{ route('tickets.index') }}" class="landing-btn">
                                Entrar al sistema
                            </a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="landing-btn">
                            Iniciar sesión
                        </a>
                    @endauth

                    <a href="#modulos" class="landing-btn-outline">
                        Ver módulos
                    </a>
                </div>

                <div class="mini-stats" aria-label="Resumen rápido">
                    <div class="mini-stat">
                        <div class="mini-stat-label">Operación</div>
                        <div class="mini-stat-value">Tickets centralizados</div>
                    </div>

                    <div class="mini-stat">
                        <div class="mini-stat-label">Usuarios</div>
                        <div class="mini-stat-value">Admin · Agente · Usuario</div>
                    </div>

                    <div class="mini-stat">
                        <div class="mini-stat-label">Seguimiento</div>
                        <div class="mini-stat-value">Estados y comentarios</div>
                    </div>
                </div>
            </section>

            <aside class="hero-side">
                <section class="side-highlight">
                    <h2>Sistema de Helpdesk</h2>
                    <p>
                        Una plataforma para registrar, atender, resolver y cerrar solicitudes de forma ordenada, profesional y trazable.
                    </p>
                </section>

                <section id="modulos" class="feature-grid" aria-label="Módulos principales">
                    <article class="feature">
                        <div class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M8 6h11M8 12h11M8 18h11"/>
                                <path d="M3 6h.01M3 12h.01M3 18h.01"/>
                            </svg>
                        </div>
                        <h3>Gestión de tickets</h3>
                        <p>Registro, clasificación y consulta de solicitudes con prioridad, categoría y estado.</p>
                    </article>

                    <article class="feature">
                        <div class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M12 3v18M3 12h18"/>
                                <circle cx="12" cy="12" r="8"/>
                            </svg>
                        </div>
                        <h3>Seguimiento operativo</h3>
                        <p>Asignación de responsables, comentarios y trazabilidad de cada ticket.</p>
                    </article>

                    <article class="feature">
                        <div class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M4 19V9m6 10V5m6 14v-7m4 7H2"/>
                            </svg>
                        </div>
                        <h3>Panel de métricas</h3>
                        <p>Visualización de indicadores para tiempos de atención, carga de trabajo y control.</p>
                    </article>
                </section>
            </aside>
        </main>
    </div>
</body>
</html>