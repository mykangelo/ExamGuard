@extends('layouts.app')

@section('title', 'ExamGuard')

@section('content')
<header class="border-b border-white/10 bg-slate-950/50 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <a href="/" class="eg-brand flex items-center gap-3 text-lg font-bold"><span>EG</span> ExamGuard</a>
        <nav class="flex items-center gap-4 text-sm text-slate-300">
            <a href="/login" class="hover:text-white">Login</a>
            <a href="/professor" class="hover:text-white">Professor</a>
            <a href="/student" class="hover:text-white">Student</a>
        </nav>
    </div>
</header>

<main class="mx-auto grid max-w-6xl gap-8 px-6 py-12 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
    <section class="space-y-6">
        <div class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-300">Online Examination Monitoring</div>
        <h1 class="text-4xl font-bold leading-tight md:text-5xl">Professional supervision for external online exams.</h1>
        <p class="max-w-2xl text-lg text-slate-300">
            ExamGuard provides a controlled online examination environment focused on webcam monitoring, browser activity detection, warning enforcement, and violation logging.
        </p>
        <div class="flex flex-wrap gap-4">
            <a href="/login" class="eg-btn-primary">Get Started</a>
            <a href="/professor" class="eg-btn-secondary">View Professor Interface</a>
        </div>
    </section>

    <section class="eg-panel">
        <div class="mb-6 flex items-center justify-between">
            <strong>Monitoring Overview</strong>
            <span class="eg-badge-success">Active</span>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl bg-white/5 p-4"><span class="text-sm text-slate-400">Exam Source</span><div class="mt-2 font-semibold">Built-in MCQ</div></div>
            <div class="rounded-2xl bg-white/5 p-4"><span class="text-sm text-slate-400">Monitoring</span><div class="mt-2 font-semibold">Camera + Activity</div></div>
            <div class="rounded-2xl bg-white/5 p-4"><span class="text-sm text-slate-400">Logs</span><div class="mt-2 font-semibold">Violation Records</div></div>
        </div>
    </section>
</main>

<section class="mx-auto max-w-6xl px-6 pb-16">
    <div class="mb-8">
        <div class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-300">How it Works</div>
        <h2 class="mt-2 text-3xl font-bold">ExamGuard supervises the online exam session.</h2>
    </div>
    <div class="grid gap-6 md:grid-cols-3">
        @foreach ([
            ['01', 'Professor creates exam', 'The professor builds class exams with questions, time limits, and warning rules.'],
            ['02', 'Student joins class', 'Students enroll with a class code and take assigned exams under monitoring.'],
            ['03', 'System records violations', 'ExamGuard tracks tab switching, camera status, warning count, and session activity.'],
        ] as [$label, $title, $text])
        <article class="eg-panel">
            <div class="mb-3 text-sm font-semibold text-sky-300">{{ $label }}</div>
            <h3 class="mb-2 text-xl font-semibold">{{ $title }}</h3>
            <p class="text-slate-300">{{ $text }}</p>
        </article>
        @endforeach
    </div>
</section>

<footer class="border-t border-white/10 py-8 text-center text-sm text-slate-400">© {{ date('Y') }} ExamGuard. Examination monitoring platform.</footer>
@endsection
