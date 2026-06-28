<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ExamGuard')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700;9..40,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    @stack('head')
    <style>
        body {
            background: #f8fafc !important;
            color: #1e293b !important;
            font-family: 'DM Sans', ui-sans-serif, sans-serif;
        }

        .auth-input {
            display: block;
            width: 100%;
            background: #fff;
            border: 1px solid rgba(0,0,0,0.12);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            color: #1e293b;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .auth-input::placeholder { color: rgba(0,0,0,0.28); }
        .auth-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
        }
        .auth-input option { background: #fff; color: #1e293b; }

        /* ── Auth panel nav buttons ──────────────────── */
        .auth-nav-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 36px;
            width: 36px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.20);
            background: transparent;
            cursor: pointer;
            transition: background 0.2s ease;
            flex-shrink: 0;
        }
        .auth-nav-btn:hover { background: rgba(255,255,255,0.09); }

        .auth-back-link { transition: color 0.2s ease; }
        .auth-back-link:hover { color: rgba(255,255,255,0.75) !important; }

        /* ── Slide transition animations ──────────── */
        @keyframes authCaptionIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0);    }
        }
        @keyframes authCardIn {
            from { opacity: 0.65; transform: scale(0.97); }
            to   { opacity: 1;    transform: scale(1);    }
        }
    </style>
</head>
<body class="antialiased min-h-screen">
    @yield('content')
    @stack('scripts')
</body>
</html>
