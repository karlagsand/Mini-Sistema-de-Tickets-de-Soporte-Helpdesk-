<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Helpdesk</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f7fb;
            color: #1f2937;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .card {
            background: #ffffff;
            width: 90%;
            max-width: 700px;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            text-align: center;
        }

        h1 {
            margin-bottom: 10px;
            color: #0f172a;
            font-size: 2.2rem;
        }

        h2 {
            margin-top: 0;
            color: #2563eb;
            font-size: 1.2rem;
            font-weight: normal;
        }

        p {
            line-height: 1.7;
            font-size: 1rem;
            margin: 15px 0;
        }

        .badge {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 18px;
            border-radius: 999px;
            background: #dbeafe;
            color: #1d4ed8;
            font-weight: bold;
            font-size: 0.95rem;
        }

        .footer {
            margin-top: 25px;
            font-size: 0.9rem;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Proyecto Helpdesk</h1>
        <h2>Sistema de gestión y atención de solicitudes</h2>

        <p>
            Este proyecto tiene como propósito centralizar la recepción, seguimiento
            y administración de solicitudes o incidencias mediante una plataforma web
            desarrollada en Laravel y conectada a una base de datos MySQL.
        </p>

        <p>
            Esta pantalla se muestra como evidencia inicial de publicación segura del proyecto
            mediante un enlace HTTPS generado con ngrok.
        </p>

        <div class="badge">Conexión segura HTTPS activa</div>

        <div class="footer">
            Evidencia de desarrollo del proyecto · Laravel + MySQL + ngrok
        </div>
    </div>
</body>
</html>