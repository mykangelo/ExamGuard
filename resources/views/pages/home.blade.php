@extends('layouts.marketing')
@section('title', 'ExamGuard — Secure Online Exam Monitoring Platform')

@section('content')

@include('partials.marketing-header', ['activePage' => 'home'])

{{-- ══ HERO ═══════════════════════════════════════════════════════════════ --}}
<section class="bg-[#0f1e3d] px-6 pb-0 pt-20 md:pt-28">
    <div class="mx-auto max-w-3xl text-center">

        {{-- Eyebrow --}}
        <p class="tf-reveal mb-6 text-[12px] font-semibold uppercase tracking-[0.18em] text-[#3b82f6]">
            Online Exam Monitoring
        </p>

        {{-- Headline --}}
        <h1 class="hero-headline mkt-display tf-reveal mb-6 font-bold text-white"
            style="font-size: clamp(3rem, 7.5vw, 5.5rem); line-height: 1.05; letter-spacing: -0.01em; transition-delay: 80ms;">
            Your exams.<br>Now fully monitored.
        </h1>

        {{-- Subheadline --}}
        <p class="tf-reveal mx-auto mb-10 max-w-xl text-[18px] leading-[1.65] text-white/65"
           style="transition-delay: 200ms;">
            ExamGuard lets professors build MCQ exams, monitor student sessions in real time via webcam,
            detect violations automatically, and review results instantly — all in one place.
        </p>

        {{-- Single CTA --}}
        <div class="tf-reveal" style="transition-delay: 320ms;">
            <a href="/login"
               class="inline-flex items-center justify-center rounded-full bg-white px-10 py-4 text-[16px] font-semibold text-[#0f1e3d] transition hover:scale-[1.03] hover:bg-white/90">
                Get started — it's free
            </a>
        </div>
    </div>

    {{-- Feature cards (Typeform ASK / ACT / LEARN style) --}}
    <div class="mx-auto mt-20 max-w-6xl">
        <div class="grid gap-4 md:grid-cols-3">

            @php
            $heroCards = [
                ['MONITOR',  'Real-time monitoring',   null,  'Watch live webcam feeds, flag missing faces, catch tab switches, and auto-log every violation the moment it happens.', '#3b82f6'],
                ['ANALYZE',  'Instant analytics',      'NEW', 'View per-student violation timelines, score distributions, and detailed session reports the moment exams close.',      '#6366f1'],
                ['CERTIFY',  'Certificate generation', null,  'Auto-issue certificates to students who reach your pass-mark threshold — fully customizable, no manual steps.',        '#0ea5e9'],
            ];
            @endphp

            @foreach($heroCards as $idx => [$label, $name, $badge, $desc, $accent])
            <div class="tf-reveal group relative overflow-hidden rounded-xl border border-white/[0.08] bg-[#162444] p-6 transition-all duration-200 hover:border-white/[0.18]"
                 style="transition-delay: {{ 420 + $idx * 100 }}ms;">
                {{-- Category label --}}
                <p class="mb-3 text-[11px] font-semibold uppercase tracking-[0.12em] text-white/45">{{ $label }}</p>

                {{-- Name + optional badge --}}
                <div class="mb-3 flex items-center gap-2.5">
                    <h3 class="text-[20px] font-[600] text-white">{{ $name }}</h3>
                    @if($badge)
                    <span class="rounded-full bg-[#6366f1] px-2.5 py-0.5 text-[11px] font-semibold text-white">{{ $badge }}</span>
                    @endif
                </div>

                {{-- Description --}}
                <p class="text-[14px] leading-[1.65] text-white/55">{{ $desc }}</p>

                {{-- Accent bottom line --}}
                <div class="absolute bottom-0 left-0 h-[2px] w-full opacity-70"
                     style="background-color: {{ $accent }};"></div>
            </div>
            @endforeach

        </div>
    </div>
</section>

{{-- ── Curved bleed bridge: navy → white ── --}}
<svg viewBox="0 0 1440 260" xmlns="http://www.w3.org/2000/svg"
     class="block w-full" preserveAspectRatio="none" style="height:260px; display:block; margin-top:-1px;">
    <defs>
        <linearGradient id="heroBleed" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%"   stop-color="#0f1e3d"/>
            <stop offset="18%"  stop-color="#112244"/>
            <stop offset="34%"  stop-color="#1d3a60"/>
            <stop offset="52%"  stop-color="#3d6e9e"/>
            <stop offset="68%"  stop-color="#7aaecb"/>
            <stop offset="82%"  stop-color="#c0daea"/>
            <stop offset="93%"  stop-color="#e8f4fb"/>
            <stop offset="100%" stop-color="#ffffff"/>
        </linearGradient>
    </defs>
    <rect width="1440" height="260" fill="url(#heroBleed)"/>
    <path d="M0,140 C360,260 1080,260 1440,140 L1440,260 L0,260 Z" fill="white"/>
</svg>

{{-- ══ HOW IT WORKS — light white ══════════════════════════════════════════ --}}
<section id="how-it-works" class="bg-white px-6 py-24">
    <div class="mx-auto max-w-4xl">

        <div class="mb-12 text-center">
            <p class="tf-reveal mb-3 text-[12px] font-semibold uppercase tracking-[0.18em] text-[#3b82f6]">How it works</p>
            <h2 class="tf-reveal text-[2rem] font-[700] leading-[1.1] tracking-[-0.02em] text-slate-900 md:text-[2.5rem]"
                style="transition-delay: 80ms;">
                Up and running in minutes
            </h2>
        </div>

        <div class="tf-reveal grid gap-4 sm:grid-cols-2 lg:grid-cols-4" style="transition-delay: 160ms;">
            @foreach([
                ['01', 'Sign up',        'Professors register; students join via a 6-character class code.'],
                ['02', 'Build your exam','Add MCQ questions, mark correct answers, and set a time limit.'],
                ['03', 'Go live',        'Students open the exam, grant webcam access, and begin under monitoring.'],
                ['04', 'Review results', 'Instant scores and a full timestamped violation report per student.'],
            ] as [$num, $title, $desc])
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-6 shadow-sm">
                <span class="mb-4 block text-[2rem] font-[800] leading-none text-[#3b82f6]/30 tabular-nums">{{ $num }}</span>
                <h3 class="mb-2 text-[16px] font-[600] text-slate-900">{{ $title }}</h3>
                <p class="text-[14px] leading-[1.65] text-slate-500">{{ $desc }}</p>
            </div>
            @endforeach
        </div>

    </div>
</section>

{{-- ══ TRUST / STATS — light gray ══════════════════════════════════════════ --}}
<section id="trust" class="bg-[#f8fafc] px-6 py-28 md:py-32">
    <div class="mx-auto max-w-5xl">

        <div class="mb-16 text-center">
            <h2 class="tf-reveal text-[2.4rem] font-[700] leading-[1.1] tracking-[-0.02em] text-slate-900 md:text-[3rem]">
                Trusted by educators and institutions worldwide.
            </h2>
        </div>

        {{-- Large stat numbers --}}
        <div class="tf-reveal mb-16 grid grid-cols-2 gap-12 text-center md:grid-cols-4"
             style="transition-delay: 80ms;">
            @foreach([
                ['12k+', 'Students',          'text-[#0f1e3d]'],
                ['50+',  'Institutions',       'text-[#0f1e3d]'],
                ['99%',  'Detection accuracy', 'text-[#3b82f6]'],
                ['3+',   'Years in service',   'text-[#0f1e3d]'],
            ] as [$num, $lbl, $color])
            <div>
                <div class="{{ $color }} font-[800] leading-none tracking-tight"
                     style="font-size: clamp(3.2rem, 6vw, 4.5rem); font-family: 'DM Sans', sans-serif;">{{ $num }}</div>
                <div class="mt-3 text-[15px] text-slate-500">{{ $lbl }}</div>
            </div>
            @endforeach
        </div>

        {{-- Institution names --}}
        <div class="tf-reveal flex flex-wrap items-center justify-center gap-x-10 gap-y-4"
             style="transition-delay: 160ms;">
            @foreach(['Universitas Teknologi','Mapúa MCL','AMA University','FEU Institute','DLSU Manila','NU Philippines','PUP Manila'] as $name)
            <span class="text-[14px] font-semibold tracking-wide text-slate-400">{{ $name }}</span>
            @endforeach
        </div>

    </div>
</section>

{{-- ══ VIDEO / DEMO SPLIT — light white ════════════════════════════════════ --}}
<section class="bg-white px-6 py-28 md:py-32">
    <div class="mx-auto max-w-5xl">

        <p class="tf-reveal mb-4 text-[12px] font-semibold uppercase tracking-[0.18em] text-[#3b82f6]">See it in action</p>
        <h2 class="tf-reveal text-[2.4rem] font-[700] leading-[1.1] tracking-[-0.02em] text-slate-900 md:text-[3rem]"
            style="transition-delay: 80ms;">
            See ExamGuard in action
        </h2>

        <div class="mt-14 grid items-stretch gap-6 md:grid-cols-2">

            {{-- Video card --}}
            <div class="tf-reveal" style="transition-delay: 140ms;">
                <div class="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-100 bg-slate-50 shadow-sm transition hover:border-slate-200 hover:shadow-md">
                    <div class="aspect-video p-7 flex flex-col justify-between">
                        <span class="text-[12px] text-slate-400">ExamGuard Tutorial Series</span>
                        <div class="space-y-2">
                            <p class="text-[19px] font-[600] leading-snug text-slate-900">Creating and assigning exams</p>
                            <p class="text-[15px] text-slate-500">Build an MCQ exam and assign it to a class in under 5 minutes.</p>
                        </div>
                    </div>
                    <div class="absolute inset-0 flex items-center justify-center bg-slate-900/5 transition group-hover:bg-slate-900/10">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white shadow-lg transition group-hover:scale-105">
                            <svg class="ml-1 h-6 w-6 text-[#0f1e3d]" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        </div>
                    </div>
                </div>
                <p class="mt-3 text-center text-[13px] text-slate-400">Short videos show you how to get started in minutes.</p>
            </div>

            {{-- Demo card --}}
            <div class="tf-reveal flex flex-col" style="transition-delay: 220ms;">
                <div class="flex flex-1 flex-col justify-between space-y-6 rounded-2xl border border-slate-100 bg-slate-50 p-7 shadow-sm">
                    <div class="space-y-4">
                        <span class="inline-block rounded-full border border-[#3b82f6]/20 bg-[#3b82f6]/8 px-3 py-1 text-[12px] font-semibold text-[#3b82f6]">Live demonstration</span>
                        <p class="text-[19px] font-[600] leading-snug text-slate-900">Experience the exam interface</p>
                        <ul class="space-y-3">
                            @foreach(['Real-time camera monitoring','Tab-switch detection active','Auto-graded on submission'] as $item)
                            <li class="flex items-center gap-3 text-[16px] text-slate-700">
                                <svg class="h-5 w-5 shrink-0 text-[#3b82f6]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                {{ $item }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="space-y-3">
                        <p class="text-[15px] leading-relaxed text-slate-500">Students experience a clean, distraction-free interface with built-in monitoring — no extra software required.</p>
                        <a href="/login"
                           class="block w-full rounded-full bg-[#3b82f6] py-3.5 text-center text-[15px] font-semibold text-white transition hover:brightness-110">
                            Try demo exams
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ══ PROCTORING SPOTLIGHT — light gray ═══════════════════════════════════ --}}
<section id="proctoring" class="bg-[#f8fafc] px-6 py-28 md:py-32">
    <div class="mx-auto grid max-w-5xl items-center gap-14 md:grid-cols-2">

        <div class="tf-reveal space-y-6">
            <span class="inline-block rounded-full border border-[#3b82f6]/20 bg-[#3b82f6]/10 px-4 py-1.5 text-[12px] font-semibold uppercase tracking-wider text-[#3b82f6]">ExamGuard Monitor</span>
            <h2 class="text-[2.4rem] font-[700] leading-[1.1] tracking-[-0.02em] text-slate-900">
                AI-powered proctoring, built right in.
            </h2>
            <p class="text-[18px] leading-[1.7] text-slate-600">
                Enable secure, automated proctoring with one click — no downloads, no installs. Runs entirely in the student's browser.
            </p>
            <ul class="space-y-4">
                @foreach(['Tab switching detection','Mouse boundary restrictions','Webcam camera monitoring','AI face detection (MediaPipe)','Require student ID verification','Timestamped violation event log'] as $item)
                <li class="flex items-center gap-3 text-[17px] text-slate-700">
                    <svg class="h-5 w-5 shrink-0 text-[#3b82f6]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    {{ $item }}
                </li>
                @endforeach
            </ul>
            <a href="/tour#monitoring"
               class="inline-flex items-center gap-2 text-[15px] font-medium text-[#3b82f6] transition hover:gap-3">
                Learn more about ExamGuard Monitor
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        {{-- Monitoring mockup — stays dark internally --}}
        <div class="tf-reveal" style="transition-delay: 130ms;">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-[#0d1b33] shadow-lg">
                <div class="flex items-center justify-between border-b border-white/[0.08] px-4 py-3">
                    <span class="text-[12px] font-semibold text-white">Session — Juan Dela Cruz</span>
                    <span class="flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-1 text-[10px] text-emerald-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Active
                    </span>
                </div>
                <div class="space-y-3 p-4">
                    <div class="flex h-28 items-center justify-center rounded-xl border border-white/[0.07] bg-slate-800/60">
                        <div class="text-center">
                            <svg class="mx-auto mb-1.5 h-7 w-7 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.069A1 1 0 0121 8.845v6.31a1 1 0 01-1.447.894L15 14M3 8a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8z"/></svg>
                            <span class="text-[10px] text-emerald-400">Face detected — monitoring active</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach([['Tab focus','Normal','text-emerald-400'],['Face detection','Clear','text-emerald-400'],['Tab switches','0','text-emerald-400'],['Warnings','2','text-amber-400']] as [$l,$v,$c])
                        <div class="rounded-lg bg-white/[0.04] px-3 py-2">
                            <div class="text-[10px] text-white/35">{{ $l }}</div>
                            <div class="text-xs font-semibold {{ $c }}">{{ $v }}</div>
                        </div>
                        @endforeach
                    </div>
                    <div>
                        <div class="mb-2 text-[10px] text-white/30">Event log</div>
                        @foreach([['14:32:07','No face detected (6 s)','text-amber-400'],['14:31:44','Tab switch detected','text-amber-400'],['14:30:12','Session started','text-white/40']] as [$ts,$ev,$c])
                        <div class="flex items-center justify-between border-b border-white/[0.05] py-1.5 last:border-0">
                            <span class="text-[10px] {{ $c }}">{{ $ev }}</span>
                            <span class="text-[9px] text-white/25">{{ $ts }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

{{-- ══ FLOW DIAGRAM — light white ═══════════════════════════════════════════ --}}
<section class="bg-white px-6 py-28 md:py-32">
    <div class="mx-auto max-w-6xl">

        <div class="mb-16 text-center">
            <p class="tf-reveal mb-4 text-[12px] font-semibold uppercase tracking-[0.18em] text-[#3b82f6]">Platform overview</p>
            <h2 class="tf-reveal text-[2.4rem] font-[700] leading-[1.1] tracking-[-0.02em] text-slate-900 md:text-[3rem]"
                style="transition-delay: 80ms;">
                How ExamGuard works
            </h2>
        </div>

        <div class="overflow-x-auto pb-2">
            <div class="flex min-w-max items-start gap-0 md:min-w-0">
                @php
                $flowSteps = [
                    ['M12 4v16m8-8H4','Create',['Add MCQ questions.','Reuse question banks.','Randomize answer order.','Set time limits.'],'/tour#exam-builder'],
                    ['M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z','Setup',['Private or public access.','Limit exam attempts.','Enable proctoring.','Assign to classes.'],'/tour#professor-flow'],
                    ['M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2','Give exam',['Works on any device.','Camera monitoring active.','Violations auto-logged.','Auto-submit on timer end.'],'/tour#student-flow'],
                    ['M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z','Analyze results',['Instant score calculation.','Per-student violation logs.','Warning count summary.','Export-ready data.'],'/tour#violation-reports'],
                    ['M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z','Certification',['Issue on completion.','Set pass-mark threshold.','Custom certificate design.','Student portal download.'],'/tour#instructions'],
                ];
                @endphp
                @foreach($flowSteps as $i => [$icon, $title, $bullets, $link])
                <div class="tf-reveal flex w-52 shrink-0 flex-col gap-4 px-4 md:w-auto md:flex-1"
                     style="transition-delay: {{ $i * 90 }}ms;">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#3b82f6]/10">
                        <svg class="h-5 w-5 text-[#3b82f6]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                    </div>
                    <p class="text-[17px] font-[600] text-slate-900">{{ $title }}</p>
                    <ul class="space-y-1.5">
                        @foreach($bullets as $b)
                        <li class="text-[14px] text-slate-500">{{ $b }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ $link }}"
                       class="mt-auto text-[14px] font-medium text-[#3b82f6] transition hover:text-blue-700">{{ $title }} →</a>
                </div>
                @if(!$loop->last)
                <div class="flex w-8 shrink-0 items-center justify-center pt-7 text-slate-300">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </div>
                @endif
                @endforeach
            </div>
        </div>

    </div>
</section>

{{-- ══ FEATURES GRID — light gray ═══════════════════════════════════════════ --}}
<section id="features" class="bg-[#f8fafc] px-6 pt-28 pb-0 md:pt-32">
    <div class="mx-auto max-w-5xl">

        <div class="mb-16">
            <p class="tf-reveal mb-4 text-[12px] font-semibold uppercase tracking-[0.18em] text-[#3b82f6]">Features</p>
            <h2 class="tf-reveal text-[2.4rem] font-[700] leading-[1.1] tracking-[-0.02em] text-slate-900 md:text-[3rem]"
                style="transition-delay: 80ms;">
                Flexible, customizable, and secure.
            </h2>
            <p class="tf-reveal mt-4 text-[18px] leading-[1.7] text-slate-600" style="transition-delay: 150ms;">
                ExamGuard features loved by educators and institutions.
            </p>
        </div>

        <div class="grid gap-x-12 gap-y-9 md:grid-cols-2">
            @foreach([
                ['M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z','Security','Session auth, CSRF protection, and HttpOnly cookies.'],
                ['M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01','Custom branding','Customize exam appearance to match your institution.'],
                ['M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4','Question banks','Build and reuse question sets grouped by topic.'],
                ['M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z','Time limits','Per-exam countdown with automatic submission on expiry.'],
                ['M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z','Instant grading','Automatic score calculation delivered on submission.'],
                ['M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z','Violation reports','Full timestamped log of camera, tab, and focus events.'],
                ['M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z','Certificates','Auto-issue certificates with configurable pass marks.'],
                ['M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4','API access','REST JSON API for LMS integrations and automation.'],
            ] as $i => [$path, $title, $desc])
            <div class="tf-reveal flex items-start gap-5"
                 style="transition-delay: {{ ($i % 4) * 70 }}ms;">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#3b82f6]/10">
                    <svg class="h-6 w-6 text-[#3b82f6]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/></svg>
                </div>
                <div>
                    <h3 class="text-[18px] font-[600] text-slate-900">{{ $title }}</h3>
                    <p class="mt-1.5 text-[16px] leading-[1.6] text-slate-500">{{ $desc }}</p>
                </div>
            </div>
            @endforeach
        </div>

        <div class="tf-reveal mt-12" style="transition-delay: 80ms;">
            <a href="/tour"
               class="inline-flex items-center gap-2 rounded-full border border-[#3b82f6]/30 px-7 py-3 text-[15px] font-medium text-[#3b82f6] transition hover:border-[#3b82f6] hover:bg-[#3b82f6]/5">
                See all features
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

    </div>
</section>

{{-- ── Straight bleed bridge: white → navy ── --}}
<div style="height:180px; background: linear-gradient(to bottom,
    #f8fafc 0%,
    #edf5fb 14%,
    #c0daea 28%,
    #7aaecb 44%,
    #3d6e9e 60%,
    #1d3a60 76%,
    #112244 90%,
    #0f1e3d 100%);"></div>

{{-- ══ CTA BANNER ══════════════════════════════════════════════════════════ --}}
<section class="bg-[#0f1e3d] px-6 py-28 text-center md:py-36">
    <div class="tf-reveal mx-auto max-w-2xl space-y-8">
        <p class="text-[12px] font-semibold uppercase tracking-[0.18em] text-[#3b82f6]">Get started</p>
        <h2 class="mkt-display text-[2.5rem] font-bold leading-[1.05] text-white md:text-[3.5rem]">
            Start monitoring your exams today.
        </h2>
        <p class="text-[18px] leading-[1.7] text-white/60">
            Join educators and institutions that trust ExamGuard for secure, monitored online assessments.
        </p>
        <div class="flex flex-wrap items-center justify-center gap-4 pt-2">
            <a href="/login"
               class="inline-flex items-center justify-center rounded-full bg-white px-10 py-4 text-[16px] font-semibold text-[#0f1e3d] transition hover:scale-[1.03] hover:bg-white/90 cta-pulse">
                Get started — it's free
            </a>
            <a href="/pricing"
               class="inline-flex items-center justify-center rounded-full border border-white/20 px-10 py-4 text-[16px] font-medium text-white/70 transition hover:border-white/40 hover:text-white">
                View pricing
            </a>
        </div>
    </div>
</section>

@include('partials.marketing-footer')

@endsection

@push('scripts')
<script src="/js/api-client.js"></script>
<script src="/js/home.js"></script>
@endpush
