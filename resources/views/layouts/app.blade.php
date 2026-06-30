<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ExamGuard')</title>
    @include('partials.pwa-head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        body { font-family: 'Plus Jakarta Sans', ui-sans-serif, sans-serif; }
        .eg-footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 12px;
            z-index: 5;
            display: flex;
            justify-content: center;
            pointer-events: none;
        }
        body.eg-shell-body {
            --eg-sidebar-width: 220px;
        }
        body.eg-shell-body .eg-footer {
            left: var(--eg-sidebar-width);
        }
        @media (max-width: 900px) {
            body.eg-shell-body[data-role="professor"] {
                --eg-sidebar-width: 64px;
            }
        }
        @media (max-width: 768px) {
            body.eg-shell-body[data-role="student"] {
                --eg-sidebar-width: 64px;
            }
        }
        .eg-footer-inner {
            pointer-events: auto;
            font-size: 11px;
            color: rgba(255,255,255,0.35);
            background: rgba(15, 30, 61, 0.55);
            border: 0.5px solid rgba(255,255,255,0.10);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 999px;
            padding: 6px 12px;
        }
    </style>
    @stack('head')
</head>
<body @yield('body_attrs')>
    @yield('content')
    <footer class="eg-footer" aria-label="Site footer">
        <div class="eg-footer-inner">© {{ date('Y') }} ExamGuard. All rights reserved.</div>
    </footer>
    <script src="/js/route-state.js?v=3"></script>
    @stack('scripts')
</body>
</html>
