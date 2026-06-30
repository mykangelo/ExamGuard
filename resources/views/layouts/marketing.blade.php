<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ExamGuard')</title>
    @include('partials.pwa-head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Lora:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    @stack('head')
    <style>
        /* Page canvas — sections control their own backgrounds */
        body {
            background: #f8fafc !important;
            background-image: none !important;
            color: #1e293b !important;
        }

        /* Display / editorial serif for hero headlines */
        .mkt-display {
            font-family: 'Lora', Georgia, serif;
        }

        /* Sans for UI and body */
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Plus Jakarta Sans', ui-sans-serif, sans-serif;
        }

        /* Anchor offset: header + iOS safe area */
        :root {
            --mkt-header-h: 72px;
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-left: env(safe-area-inset-left, 0px);
            --safe-right: env(safe-area-inset-right, 0px);
        }
        [id] { scroll-margin-top: calc(var(--mkt-header-h) + var(--safe-top)); }

        /* Sticky marketing nav — clear iPhone status bar / notch */
        .mkt-site-header {
            padding-top: var(--safe-top);
            padding-left: var(--safe-left);
            padding-right: var(--safe-right);
        }

        /* Auth pages on mobile — full-width shell below status bar */
        @media (max-width: 1023px) {
            .auth-mobile-shell {
                padding-top: var(--safe-top);
                padding-left: var(--safe-left);
                padding-right: var(--safe-right);
            }
        }
    </style>
</head>
<body class="antialiased">
    @yield('content')
    <script src="/js/route-state.js?v=2"></script>
    <script src="/js/marketing-nav.js?v=1"></script>
    @stack('scripts')
</body>
</html>
