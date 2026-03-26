<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ups! Algo salió mal - SGCC</title>
    <link rel="icon" type="image/webp" href="{{ asset('assets/img/unnamed.webp') }}">

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --error: #ef4444;
            --bg: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        /* Ambient Background Glow */
        body::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(79, 70, 229, 0.15) 0%, rgba(15, 23, 42, 0) 70%);
            top: -100px;
            right: -100px;
            z-index: -1;
        }

        body::after {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(239, 68, 68, 0.1) 0%, rgba(15, 23, 42, 0) 70%);
            bottom: -150px;
            left: -150px;
            z-index: -1;
        }

        .container {
            max-width: 600px;
            width: 90%;
            text-align: center;
            padding: 3rem;
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .icon-box {
            width: 80px;
            height: 80px;
            background: rgba(239, 68, 68, 0.1);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            color: var(--error);
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        p {
            font-size: 1.125rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 2.5rem;
        }

        .actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
        }

        .btn-outline {
            background: transparent;
            color: var(--text-main);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .error-code {
            margin-top: 3rem;
            font-size: 0.875rem;
            color: rgba(148, 163, 184, 0.5);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        svg {
            width: 48px;
            height: 48px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="icon-box">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
        </div>
        <img src="/img/errors/500.png" alt="Error Illustration"
            style="width: 280px; height: auto; margin: -1rem auto 1.5rem; display: block; border-radius: 20px; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.3));">
        <h1>Ups! Lo sentimos</h1>
        <p>Ha ocurrido un error inesperado en el sistema. <br> Estamos trabajando para solucionarlo lo antes posible.
        </p>

        <div class="actions">
            <a href="/" class="btn btn-primary">
                <svg style="width: 20px; height: 20px;" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                Volver al Inicio
            </a>
            <button onclick="window.location.reload()" class="btn btn-outline">
                <svg style="width: 20px; height: 20px;" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                Reintentar
            </button>
        </div>

        <div class="error-code">Error de Servidor Interno (500)</div>
    </div>
</body>

</html>
