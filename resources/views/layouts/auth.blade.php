<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ExamGuard')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    @stack('head')
    <style>
        body {
            background: #f8fafc !important;
            color: #1e293b !important;
            font-family: 'Plus Jakarta Sans', ui-sans-serif, sans-serif;
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

        /* ── Field validation states ─────────────────── */
        .auth-input.field-error {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239,68,68,0.10);
        }
        .auth-input.field-ok {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16,185,129,0.08);
        }
        .field-msg {
            align-items: center;
            gap: 4px;
            font-size: 11.5px;
            margin-top: 5px;
            line-height: 1.4;
        }
        .field-msg:not(.hidden) { display: flex; }
        .field-msg.hidden       { display: none !important; }
        .field-msg.err  { color: #ef4444; }
        .field-msg.ok   { color: #10b981; }
        .field-msg.hint { color: #94a3b8; }
        .field-msg.warn { color: #f59e0b; }

        /* Credential-locked button state */
        .auth-input.field-warn {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245,158,11,0.10);
        }

        /* ── Error / success banners ─────────────────── */
        @keyframes bannerSlideIn {
            from { opacity: 0; transform: translateY(-8px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0)    scale(1);    }
        }
        .auth-banner-in { animation: bannerSlideIn 0.22s ease-out both; }

        /* ── Password strength bar ───────────────────── */
        .strength-track {
            height: 3px;
            border-radius: 999px;
            background: rgba(0,0,0,0.07);
            overflow: hidden;
            margin-top: 8px;
        }
        .strength-fill {
            height: 100%;
            border-radius: 999px;
            width: 0%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }

        /* ── Role button error + shake ───────────────── */
        .role-btn.role-error { border-color: #ef4444 !important; background: rgba(239,68,68,0.04) !important; }
        @keyframes fieldShake {
            0%,100% { transform: translateX(0); }
            20%,60% { transform: translateX(-5px); }
            40%,80% { transform: translateX(5px); }
        }
        .shake { animation: fieldShake 0.35s ease; }

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
    <script src="/js/route-state.js?v=2"></script>
    @stack('scripts')
</body>
</html>
