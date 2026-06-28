<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ExamGuard')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700;9..40,800&family=DM+Serif+Display&display=swap" rel="stylesheet">
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
            font-family: 'DM Serif Display', Georgia, serif;
        }

        /* Sans for UI and body */
        h1, h2, h3, h4, h5, h6 {
            font-family: 'DM Sans', Inter, ui-sans-serif, sans-serif;
        }

        /* Anchor offset: single-bar header is ~72px */
        [id] { scroll-margin-top: 72px; }
    </style>
</head>
<body class="antialiased">
    @yield('content')
    @stack('scripts')
</body>
</html>
