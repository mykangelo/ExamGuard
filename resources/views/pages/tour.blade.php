@extends('layouts.marketing')
@section('title', 'Take a Tour | ExamGuard')

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3/dist/tabler-icons.min.css">
@endpush

@section('content')
@include('partials.marketing-header', ['activePage' => 'tour'])

@php
$sidebarNav = [
    ['#overview',       'ti ti-info-circle',   'How ExamGuard works', 'overview'],
    ['#features',       'ti ti-layout-grid',   'Features',            'features'],
    ['#demo',           'ti ti-player-play',   'Try demo exams',      'demo'],
    ['#creating-exams', 'ti ti-file-plus',     'Creating exams',      'creating-exams'],
    ['#giving-exams',   'ti ti-send',          'Giving exams',        'giving-exams'],
    ['#taking-exams',   'ti ti-pencil',        'Taking exams',        'taking-exams'],
    ['#exam-results',   'ti ti-chart-bar',     'Exam results',        'exam-results'],
    ['#violations',     'ti ti-alert-triangle','Violations',          'violations'],
    ['#certificates',   'ti ti-award',         'Certificates',        'certificates'],
    ['#monitoring',     'ti ti-eye',           'ExamGuard Monitor',   'monitoring'],
    ['#api',            'ti ti-code',          'ExamGuard API',       'api'],
    ['#customers',      'ti ti-users',         'Our customers',       'customers'],
];
@endphp

{{-- ── Mobile horizontal tabs ── --}}
<div class="lg:hidden overflow-x-auto border-b border-white/[0.07] bg-[#0f1e3d] px-4 py-2.5">
    <div class="flex gap-1 min-w-max">
        @foreach($sidebarNav as [$href, $icon, $label, $id])
        <a href="{{ $href }}"
           class="tour-tab-link whitespace-nowrap rounded-full px-3.5 py-2 text-[12px] text-white/55 transition hover:bg-white/[0.05] hover:text-white"
           data-section="{{ $id }}">
            {{ $label }}
        </a>
        @endforeach
    </div>
</div>

{{-- ── Two-column layout ── --}}
<div class="bg-[#0f1e3d]">
<div class="flex max-w-5xl mx-auto">

    {{-- ══ LEFT SIDEBAR ══ --}}
    <aside class="hidden lg:block w-48 shrink-0 sticky self-start h-[calc(100vh-72px)] overflow-y-auto border-r border-white/[0.08] bg-[#0f1e3d]"
           style="top: calc(72px + env(safe-area-inset-top, 0px));">
        <nav class="py-4 px-2">
            @foreach($sidebarNav as [$href, $icon, $label, $id])
            <a href="{{ $href }}"
               class="tour-sidebar-link group flex items-center gap-2.5 rounded-r-lg border-l-[3px] border-transparent px-3 py-2.5 text-[13px] text-white/55 transition-all duration-200 hover:bg-white/[0.04] hover:text-white"
               data-section="{{ $id }}">
                <i class="{{ $icon }} text-[15px] shrink-0 transition-colors duration-200"></i>
                <span>{{ $label }}</span>
            </a>
            @endforeach
        </nav>
    </aside>

    {{-- ══ MAIN CONTENT ══ --}}
    <main class="flex-1 min-w-0 px-6 pb-16 pt-8 md:px-10">

        {{-- ════════════════════════════════════════════
             SECTION 1 — OVERVIEW
        ════════════════════════════════════════════ --}}
        <section id="overview" data-tour-section="overview" class="mb-10 scroll-mt-20">
            <p class="mb-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#3b82f6]">Overview</p>
            <h1 class="tf-reveal mb-1 text-[1.7rem] font-[700] leading-[1.15] text-white">How ExamGuard works</h1>
            <p class="tf-reveal mb-5 text-[15px] text-[#3b82f6]" style="transition-delay:80ms">Here's the simplicity of ExamGuard.</p>

            @php $ovSteps = [
                ['Create an exam',       [['ti-import','Import your questions'],['ti-database','Draw from your question bank'],['ti-tags','Categorize questions by topic']], 'builder'],
                ['Configure settings',   [['ti-clock','Set time limits and attempt rules'],['ti-lock','Restrict access by class or password'],['ti-eye','Enable AI camera proctoring']], 'settings'],
                ['Distribute',           [['ti-link','Share via a direct link'],['ti-users','Assign to enrolled class'],['ti-calendar','Schedule availability windows']], 'distribute'],
                ['Monitor live',         [['ti-activity','Watch real-time student activity'],['ti-alert-triangle','Violations flagged automatically'],['ti-video','Webcam feeds per student']], 'monitor'],
                ['Review results',       [['ti-chart-bar','Instant scores on submission'],['ti-file-report','Per-student violation logs'],['ti-download','Export data anytime']], 'results'],
            ]; @endphp

            {{-- Timeline: flex column keeps line perfectly between circles --}}
            <div class="space-y-0">
                @foreach($ovSteps as $si => [$stitle, $bullets, $mtype])
                <div class="tf-reveal flex gap-4" style="transition-delay:{{ $si * 70 }}ms">

                    {{-- Circle + vertical connector --}}
                    <div class="flex flex-col items-center">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-2 border-[#3b82f6] bg-[#0f1e3d] text-[14px] font-bold text-white z-10">{{ $si + 1 }}</div>
                        @if(!$loop->last)
                        <div class="mt-1 w-px flex-1 bg-white/[0.08] min-h-[20px]"></div>
                        @endif
                    </div>

                    {{-- Content row --}}
                    <div class="flex flex-1 flex-col gap-4 {{ !$loop->last ? 'pb-6' : '' }} md:flex-row md:gap-5">
                        <div class="min-w-0 flex-1 pt-1">
                            <h3 class="mb-2 text-[17px] font-[600] leading-snug text-white">{{ $stitle }}</h3>
                            <ul class="space-y-1.5">
                                @foreach($bullets as [$bicon, $btext])
                                <li class="flex items-center gap-2.5 text-[13px] text-white/60">
                                    <i class="ti {{ $bicon }} shrink-0 text-[15px] text-[#3b82f6]"></i>
                                    {{ $btext }}
                                </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- Mockup card --}}
                        <div class="w-full md:w-52 shrink-0 self-start rounded-xl border border-white/[0.08] bg-[#162444] p-4">
                            @if($mtype === 'builder')
                            <p class="mb-2.5 text-[10px] font-semibold uppercase tracking-wider text-white/30">Exam Builder</p>
                            <div class="space-y-1.5">
                                @foreach(['Q1: Academic integrity?','Q2: Which tool detects faces?','Q3: Exam duration?'] as $q)
                                <div class="flex items-center justify-between rounded-md bg-white/[0.04] px-3 py-2">
                                    <span class="text-[11px] text-white/65 truncate pr-2">{{ $q }}</span>
                                    <span class="text-[9px] shrink-0 text-emerald-400">MCQ</span>
                                </div>
                                @endforeach
                            </div>
                            <button class="mt-2.5 w-full rounded-md border border-dashed border-white/[0.10] py-2 text-[11px] text-white/25">+ Add question</button>

                            @elseif($mtype === 'settings')
                            <p class="mb-2.5 text-[10px] font-semibold uppercase tracking-wider text-white/30">Settings</p>
                            <div class="space-y-1.5">
                                @foreach([['Time limit','30 min'],['Attempts','1'],['Proctoring','On'],['Access','Class only']] as [$k,$v])
                                <div class="flex items-center justify-between rounded-md bg-white/[0.04] px-3 py-2">
                                    <span class="text-[11px] text-white/50">{{ $k }}</span>
                                    <span class="text-[11px] font-semibold text-white/80">{{ $v }}</span>
                                </div>
                                @endforeach
                            </div>

                            @elseif($mtype === 'distribute')
                            <p class="mb-2.5 text-[10px] font-semibold uppercase tracking-wider text-white/30">Distribution</p>
                            <div class="mb-2 flex items-center gap-2 rounded-md bg-white/[0.04] px-3 py-2">
                                <i class="ti ti-link text-[#3b82f6] text-[13px] shrink-0"></i>
                                <span class="truncate text-[11px] text-white/50">examguard.app/e/abc123</span>
                            </div>
                            @foreach([['CS101','14 students'],['CS102','22 students']] as [$cls,$cnt])
                            <div class="flex items-center justify-between rounded-md bg-white/[0.04] px-3 py-2 mb-1.5">
                                <span class="text-[11px] text-white/65">{{ $cls }}</span>
                                <span class="text-[10px] text-white/35">{{ $cnt }}</span>
                            </div>
                            @endforeach

                            @elseif($mtype === 'monitor')
                            <p class="mb-2.5 text-[10px] font-semibold uppercase tracking-wider text-white/30">Live Session</p>
                            @foreach([['Ana R.','Normal','text-emerald-400'],['Ben T.','Warning','text-amber-400'],['Cara L.','Normal','text-emerald-400']] as [$name,$status,$sc])
                            <div class="mb-1.5 flex items-center justify-between rounded-md bg-white/[0.04] px-3 py-2">
                                <span class="flex items-center gap-2 text-[11px] text-white/65">
                                    <span class="h-2 w-2 rounded-full {{ $sc }} bg-current"></span>
                                    {{ $name }}
                                </span>
                                <span class="text-[10px] {{ $sc }}">{{ $status }}</span>
                            </div>
                            @endforeach

                            @elseif($mtype === 'results')
                            <p class="mb-2.5 text-[10px] font-semibold uppercase tracking-wider text-white/30">Results</p>
                            @foreach([['Ana R.','92%'],['Ben T.','76%'],['Cara L.','88%']] as [$name,$score])
                            <div class="mb-1.5 flex items-center justify-between rounded-md bg-white/[0.04] px-3 py-2">
                                <span class="text-[11px] text-white/65">{{ $name }}</span>
                                <span class="text-[11px] font-semibold text-emerald-400">{{ $score }}</span>
                            </div>
                            @endforeach
                            @endif
                        </div>
                    </div>

                </div>
                @endforeach
            </div>
        </section>

        {{-- ════════════════════════════════════════════
             SECTION 2 — FEATURES
        ════════════════════════════════════════════ --}}
        <section id="features" data-tour-section="features" class="mb-10 scroll-mt-20">
            <p class="mb-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#3b82f6]">Features</p>
            <h2 class="tf-reveal mb-1 text-[1.9rem] font-[700] leading-[1.15] text-white">Everything you need to run fair exams</h2>
            <p class="tf-reveal mb-4 text-[17px] text-[#3b82f6]" style="transition-delay:80ms">Built for educators, trusted by institutions.</p>

            <div class="grid gap-3 sm:grid-cols-2">
                @foreach([
                    ['ti-video','Real-time monitoring','Live webcam feeds with automatic face detection during every exam session.'],
                    ['ti-alert-triangle','Violation detection','Tab switches, missing faces, and multiple faces are logged with precise timestamps.'],
                    ['ti-database','Question banks','Build reusable banks of MCQ questions organized by topic and difficulty.'],
                    ['ti-clock','Time limits','Per-exam countdown timers with automatic submission on expiry.'],
                    ['ti-check','Instant grading','Automatic score calculation the moment a student submits.'],
                    ['ti-award','Certificates','Auto-issue certificates to students who exceed your pass-mark threshold.'],
                    ['ti-brush','Custom branding','Tailor exam appearance to match your institution\'s identity.'],
                    ['ti-code','API access','REST JSON API for LMS integrations and exam automation.'],
                ] as $i => [$icon, $title, $desc])
                <div class="tf-reveal flex items-start gap-3 rounded-xl border border-white/[0.07] bg-[#162444] p-3.5"
                     style="transition-delay:{{ ($i % 4) * 60 }}ms">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#3b82f6]/15">
                        <i class="ti {{ $icon }} text-[#3b82f6] text-[16px]"></i>
                    </div>
                    <div>
                        <p class="mb-0.5 text-[13px] font-[600] text-white">{{ $title }}</p>
                        <p class="text-[12px] leading-[1.5] text-white/55">{{ $desc }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </section>

        {{-- ════════════════════════════════════════════
             SECTION 3 — DEMO
        ════════════════════════════════════════════ --}}
        <section id="demo" data-tour-section="demo" class="mb-10 scroll-mt-20">
            <p class="mb-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#3b82f6]">Demo</p>
            <h2 class="tf-reveal mb-1 text-[1.9rem] font-[700] leading-[1.15] text-white">Experience ExamGuard firsthand</h2>
            <p class="tf-reveal mb-5 text-[14px] leading-[1.6] text-white/60" style="transition-delay:80ms">
                Take an actual demo exam to see exactly what students experience — camera monitoring, timer countdown, and auto-grading on submission.
            </p>
            <div class="tf-reveal flex flex-wrap items-center gap-3 mb-6" style="transition-delay:160ms">
                <a href="/login"
                   class="inline-flex items-center gap-2 rounded-full bg-[#3b82f6] px-6 py-2.5 text-[14px] font-semibold text-white transition hover:brightness-110 hover:scale-[1.03]">
                    <i class="ti ti-player-play text-[16px]"></i>
                    Start a demo exam
                </a>
                <span class="text-[13px] text-white/40">No account required for demo exams.</span>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach([
                    ['ti-eye','Camera active','Your webcam monitors for face presence and suspicious behavior throughout.'],
                    ['ti-clock','Timer running','A visible countdown keeps students on pace. Auto-submits on zero.'],
                    ['ti-check','Instant score','Results and your violation log appear the moment you submit.'],
                ] as [$icon, $title, $desc])
                <div class="tf-reveal flex flex-col gap-2 rounded-xl border border-white/[0.07] bg-[#162444] p-3.5">
                    <i class="ti {{ $icon }} text-[#3b82f6] text-[20px]"></i>
                    <p class="text-[13px] font-[600] text-white">{{ $title }}</p>
                    <p class="text-[12px] leading-[1.5] text-white/55">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </section>

        {{-- ════════════════════════════════════════════
             SECTION 4 — CREATING EXAMS
        ════════════════════════════════════════════ --}}
        <section id="creating-exams" data-tour-section="creating-exams" class="mb-10 scroll-mt-20">
            <p class="mb-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#3b82f6]">Exam Setup</p>
            <h2 class="tf-reveal mb-1 text-[1.9rem] font-[700] leading-[1.15] text-white">Creating exams</h2>
            <p class="tf-reveal mb-4 text-[17px] text-[#3b82f6]" style="transition-delay:80ms">Build any exam in minutes.</p>

            <div class="flex flex-col gap-6 md:flex-row md:items-start md:gap-8">
                <div class="flex-1 space-y-4">
                    @foreach([
                        ['ti-file-plus','Open the Exam Builder','Name your exam and choose the target class. The builder is instantly accessible from your professor dashboard.'],
                        ['ti-list','Add MCQ questions','Write each question, add up to 4 answer choices, and mark the correct answer. Drag to reorder at any time.'],
                        ['ti-refresh','Reuse question banks','Import questions from previous exams or save new ones to your bank for future use.'],
                        ['ti-settings','Set time limit and rules','Choose how long students have, whether to randomize question order, and how many attempts are allowed.'],
                    ] as $si => [$icon, $title, $desc])
                    <div class="tf-reveal flex gap-4" style="transition-delay:{{ $si * 70 }}ms">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#3b82f6]/15 mt-0.5">
                            <i class="ti {{ $icon }} text-[#3b82f6] text-[17px]"></i>
                        </div>
                        <div>
                            <p class="mb-0.5 text-[14px] font-[600] text-white">{{ $title }}</p>
                            <p class="text-[13px] leading-[1.55] text-white/55">{{ $desc }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="w-full md:w-56 shrink-0 rounded-xl border border-white/[0.08] bg-[#162444] p-5">
                    <p class="mb-3 text-[10px] font-semibold uppercase tracking-wider text-white/30">Exam Builder</p>
                    <div class="mb-3 rounded-lg bg-white/[0.04] px-3 py-2.5">
                        <p class="text-[11px] text-white/35 mb-1">Exam title</p>
                        <p class="text-[13px] text-white/80">Midterm Exam — CS101</p>
                    </div>
                    <div class="space-y-1.5 mb-3">
                        @foreach(['Q1 — What is academic integrity?','Q2 — Define proctoring.','Q3 — Which browser is recommended?'] as $q)
                        <div class="flex items-center justify-between rounded-lg bg-white/[0.04] px-3 py-2">
                            <span class="text-[11px] text-white/60">{{ $q }}</span>
                            <span class="text-[9px] text-emerald-400">✓</span>
                        </div>
                        @endforeach
                    </div>
                    <button class="w-full rounded-lg border border-dashed border-white/[0.12] py-2 text-[11px] text-white/30">+ Add question</button>
                </div>
            </div>
        </section>

        {{-- ════════════════════════════════════════════
             SECTION 5 — GIVING EXAMS
        ════════════════════════════════════════════ --}}
        <section id="giving-exams" data-tour-section="giving-exams" class="mb-10 scroll-mt-20">
            <p class="mb-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#3b82f6]">Distribution</p>
            <h2 class="tf-reveal mb-1 text-[1.9rem] font-[700] leading-[1.15] text-white">Giving exams</h2>
            <p class="tf-reveal mb-4 text-[17px] text-[#3b82f6]" style="transition-delay:80ms">Assign, schedule, and launch.</p>

            <div class="flex flex-col gap-6 md:flex-row md:items-start md:gap-8">
                <div class="flex-1 space-y-4">
                    @foreach([
                        ['ti-users','Assign to a class','Select one or more classes from your roster. All enrolled students will see the exam on their dashboard.'],
                        ['ti-link','Or share a direct link','Generate a shareable URL for open-access exams. Anyone with the link can take it.'],
                        ['ti-calendar','Set availability windows','Optionally restrict when the exam is accessible — useful for timed exam sessions.'],
                        ['ti-eye','Toggle proctoring','Enable ExamGuard Monitor with one click to activate live camera monitoring for all takers.'],
                    ] as $si => [$icon, $title, $desc])
                    <div class="tf-reveal flex gap-4" style="transition-delay:{{ $si * 70 }}ms">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#3b82f6]/15 mt-0.5">
                            <i class="ti {{ $icon }} text-[#3b82f6] text-[17px]"></i>
                        </div>
                        <div>
                            <p class="mb-0.5 text-[14px] font-[600] text-white">{{ $title }}</p>
                            <p class="text-[13px] leading-[1.55] text-white/55">{{ $desc }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="w-full md:w-56 shrink-0 rounded-xl border border-white/[0.08] bg-[#162444] p-5">
                    <p class="mb-3 text-[10px] font-semibold uppercase tracking-wider text-white/30">Assign Exam</p>
                    @foreach([['CS101 — 14 students','Assigned','text-emerald-400'],['CS102 — 22 students','Assigned','text-emerald-400'],['CS103 — 8 students','Pending','text-white/30']] as [$cls,$st,$cls2])
                    <div class="mb-1.5 flex items-center justify-between rounded-lg bg-white/[0.04] px-3 py-2.5">
                        <span class="text-[12px] text-white/70">{{ $cls }}</span>
                        <span class="text-[11px] {{ $cls2 }}">{{ $st }}</span>
                    </div>
                    @endforeach
                    <div class="mt-3 flex items-center justify-between rounded-lg border border-[#3b82f6]/20 bg-[#3b82f6]/5 px-3 py-2">
                        <span class="text-[11px] text-white/60">Proctoring</span>
                        <span class="text-[11px] font-semibold text-[#3b82f6]">Enabled</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- ════════════════════════════════════════════
             SECTION 6 — TAKING EXAMS
        ════════════════════════════════════════════ --}}
        <section id="taking-exams" data-tour-section="taking-exams" class="mb-10 scroll-mt-20">
            <p class="mb-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#3b82f6]">Student Experience</p>
            <h2 class="tf-reveal mb-1 text-[1.9rem] font-[700] leading-[1.15] text-white">Taking exams</h2>
            <p class="tf-reveal mb-4 text-[17px] text-[#3b82f6]" style="transition-delay:80ms">The student experience — clean, focused, monitored.</p>

            <div class="flex flex-col gap-6 md:flex-row md:items-start md:gap-8">
                <div class="flex-1 space-y-4">
                    @foreach([
                        ['ti-camera','Grant webcam access','Students are prompted to allow camera access before the exam begins. No downloads required.'],
                        ['ti-writing','Answer questions','Clean MCQ interface — one question at a time or all at once depending on exam settings.'],
                        ['ti-clock','Track the timer','A visible countdown is always present. Answering on time is the student\'s responsibility.'],
                        ['ti-check','Submit and receive score','Clicking submit finalizes the attempt. Scores and a violation summary are shown immediately.'],
                    ] as $si => [$icon, $title, $desc])
                    <div class="tf-reveal flex gap-4" style="transition-delay:{{ $si * 70 }}ms">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#3b82f6]/15 mt-0.5">
                            <i class="ti {{ $icon }} text-[#3b82f6] text-[17px]"></i>
                        </div>
                        <div>
                            <p class="mb-0.5 text-[14px] font-[600] text-white">{{ $title }}</p>
                            <p class="text-[13px] leading-[1.55] text-white/55">{{ $desc }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="w-full md:w-56 shrink-0 rounded-xl border border-white/[0.08] bg-[#162444] p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-white/30">Student Exam View</p>
                        <span class="text-[11px] font-semibold text-amber-400">28:42</span>
                    </div>
                    <p class="mb-3 text-[13px] text-white/80 leading-snug">Q2 — Which component detects face presence?</p>
                    <div class="space-y-2">
                        @foreach(['MediaPipe','TensorFlow','OpenCV','FaceAPI'] as $i => $ans)
                        <div class="flex items-center gap-2.5 rounded-lg {{ $i === 0 ? 'border border-[#3b82f6]/40 bg-[#3b82f6]/10' : 'bg-white/[0.04]' }} px-3 py-2">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full border {{ $i === 0 ? 'border-[#3b82f6] text-[#3b82f6]' : 'border-white/20 text-white/30' }} text-[10px]">{{ chr(65+$i) }}</span>
                            <span class="text-[12px] {{ $i === 0 ? 'text-white' : 'text-white/50' }}">{{ $ans }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- ════════════════════════════════════════════
             SECTION 7 — EXAM RESULTS
        ════════════════════════════════════════════ --}}
        <section id="exam-results" data-tour-section="exam-results" class="mb-10 scroll-mt-20">
            <p class="mb-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#3b82f6]">Analytics</p>
            <h2 class="tf-reveal mb-1 text-[1.9rem] font-[700] leading-[1.15] text-white">Exam results</h2>
            <p class="tf-reveal mb-4 text-[17px] text-[#3b82f6]" style="transition-delay:80ms">Instant grading, no manual work.</p>

            <div class="flex flex-col gap-6 md:flex-row md:items-start md:gap-8">
                <div class="flex-1 space-y-4">
                    @foreach([
                        ['ti-calculator','Automatic scoring','Scores are calculated the moment a student submits — no professor action needed.'],
                        ['ti-chart-bar','Class-wide analytics','View score distribution, average, highest, and lowest across all students.'],
                        ['ti-file-description','Per-question breakdown','See how many students answered each question correctly to identify weak spots.'],
                        ['ti-download','Export results','Download a CSV of all student scores and attempt data for record-keeping.'],
                    ] as $si => [$icon, $title, $desc])
                    <div class="tf-reveal flex gap-4" style="transition-delay:{{ $si * 70 }}ms">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#3b82f6]/15 mt-0.5">
                            <i class="ti {{ $icon }} text-[#3b82f6] text-[17px]"></i>
                        </div>
                        <div>
                            <p class="mb-0.5 text-[14px] font-[600] text-white">{{ $title }}</p>
                            <p class="text-[13px] leading-[1.55] text-white/55">{{ $desc }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="w-full md:w-56 shrink-0 rounded-xl border border-white/[0.08] bg-[#162444] p-5">
                    <p class="mb-3 text-[10px] font-semibold uppercase tracking-wider text-white/30">Results — CS101</p>
                    <div class="mb-1 grid grid-cols-3 gap-2">
                        @foreach([['Avg','82%','text-[#3b82f6]'],['High','97%','text-emerald-400'],['Low','61%','text-amber-400']] as [$lbl,$val,$col])
                        <div class="rounded-lg bg-white/[0.04] px-2 py-2 text-center">
                            <p class="text-[10px] text-white/30">{{ $lbl }}</p>
                            <p class="text-[14px] font-bold {{ $col }}">{{ $val }}</p>
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-2 space-y-1.5">
                        @foreach([['Ana R.','92%','text-emerald-400'],['Ben T.','76%','text-white/70'],['Cara L.','88%','text-emerald-400'],['Dan M.','61%','text-amber-400']] as [$name,$score,$col])
                        <div class="flex items-center justify-between rounded-lg bg-white/[0.04] px-3 py-1.5">
                            <span class="text-[11px] text-white/60">{{ $name }}</span>
                            <span class="text-[11px] font-semibold {{ $col }}">{{ $score }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- ════════════════════════════════════════════
             SECTION 8 — VIOLATIONS
        ════════════════════════════════════════════ --}}
        <section id="violations" data-tour-section="violations" class="mb-10 scroll-mt-20">
            <p class="mb-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#3b82f6]">Integrity</p>
            <h2 class="tf-reveal mb-1 text-[1.9rem] font-[700] leading-[1.15] text-white">Violations and incident logs</h2>
            <p class="tf-reveal mb-4 text-[17px] text-[#3b82f6]" style="transition-delay:80ms">Every suspicious event, captured and timestamped.</p>

            <div class="flex flex-col gap-6 md:flex-row md:items-start md:gap-8">
                <div class="flex-1 space-y-4">
                    @foreach([
                        ['ti-eye-off','No face detected','A violation is logged if no face is detected for more than 6 consecutive seconds.'],
                        ['ti-users','Multiple faces','If more than one face appears in frame, an incident is immediately recorded.'],
                        ['ti-browser','Tab switch','Any time the student navigates away from the exam window, the event is captured.'],
                        ['ti-list-details','Complete incident timeline','Professors see a full timestamped log for each student — sorted, filterable, and exportable.'],
                    ] as $si => [$icon, $title, $desc])
                    <div class="tf-reveal flex gap-4" style="transition-delay:{{ $si * 70 }}ms">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#3b82f6]/15 mt-0.5">
                            <i class="ti {{ $icon }} text-[#3b82f6] text-[17px]"></i>
                        </div>
                        <div>
                            <p class="mb-0.5 text-[14px] font-[600] text-white">{{ $title }}</p>
                            <p class="text-[13px] leading-[1.55] text-white/55">{{ $desc }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="w-full md:w-56 shrink-0 rounded-xl border border-white/[0.08] bg-[#162444] p-5">
                    <p class="mb-3 text-[10px] font-semibold uppercase tracking-wider text-white/30">Violation Log — Ben T.</p>
                    <div class="space-y-1.5">
                        @foreach([['14:32:07','No face detected (8 s)','text-red-400'],['14:31:44','Tab switch detected','text-amber-400'],['14:28:02','Multiple faces (3 s)','text-red-400'],['14:20:00','Session started','text-white/35']] as [$ts,$ev,$col])
                        <div class="flex items-center justify-between rounded-lg bg-white/[0.04] px-3 py-2">
                            <span class="text-[11px] {{ $col }}">{{ $ev }}</span>
                            <span class="text-[9px] text-white/25">{{ $ts }}</span>
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-3 flex items-center gap-2 rounded-lg bg-red-500/10 px-3 py-2">
                        <i class="ti ti-alert-triangle text-red-400 text-[14px]"></i>
                        <span class="text-[11px] text-red-400">3 violations — review required</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- ════════════════════════════════════════════
             SECTION 9 — CERTIFICATES
        ════════════════════════════════════════════ --}}
        <section id="certificates" data-tour-section="certificates" class="mb-10 scroll-mt-20">
            <p class="mb-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#3b82f6]">Achievement</p>
            <h2 class="tf-reveal mb-1 text-[1.9rem] font-[700] leading-[1.15] text-white">Certificates</h2>
            <p class="tf-reveal mb-4 text-[17px] text-[#3b82f6]" style="transition-delay:80ms">Reward achievement automatically.</p>

            <div class="flex flex-col gap-6 md:flex-row md:items-start md:gap-8">
                <div class="flex-1 space-y-4">
                    @foreach([
                        ['ti-settings','Set a pass-mark threshold','Define the minimum score required for a certificate. Each exam can have its own threshold.'],
                        ['ti-award','Auto-issued on passing','When a student\'s score meets the threshold, a certificate is generated and attached to their result.'],
                        ['ti-download','Student can download','Certificates are accessible from the student portal as downloadable documents.'],
                        ['ti-id','Custom certificate design','Include your institution name, the exam title, date, and student name on every certificate.'],
                    ] as $si => [$icon, $title, $desc])
                    <div class="tf-reveal flex gap-4" style="transition-delay:{{ $si * 70 }}ms">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#3b82f6]/15 mt-0.5">
                            <i class="ti {{ $icon }} text-[#3b82f6] text-[17px]"></i>
                        </div>
                        <div>
                            <p class="mb-0.5 text-[14px] font-[600] text-white">{{ $title }}</p>
                            <p class="text-[13px] leading-[1.55] text-white/55">{{ $desc }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="w-full md:w-56 shrink-0 rounded-xl border border-white/[0.08] bg-[#162444] p-5">
                    <div class="rounded-lg border border-[#3b82f6]/20 bg-[#0f1e3d] p-4 text-center">
                        <i class="ti ti-award text-[#3b82f6] text-[32px] block mb-2"></i>
                        <p class="text-[10px] uppercase tracking-wider text-white/30 mb-1">Certificate of Completion</p>
                        <p class="text-[13px] font-semibold text-white mb-1">Ana Reyes</p>
                        <p class="text-[11px] text-white/50 mb-2">CS101 — Midterm Exam</p>
                        <p class="text-[10px] text-emerald-400">Score: 92% · Passed</p>
                        <div class="mt-3 border-t border-white/[0.07] pt-3">
                            <p class="text-[9px] text-white/20">Issued by ExamGuard · June 2026</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- ════════════════════════════════════════════
             SECTION 10 — EXAMGUARD MONITOR
        ════════════════════════════════════════════ --}}
        <section id="monitoring" data-tour-section="monitoring" class="mb-10 scroll-mt-20">
            <p class="mb-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#3b82f6]">Proctoring</p>
            <h2 class="tf-reveal mb-1 text-[1.9rem] font-[700] leading-[1.15] text-white">ExamGuard Monitor — AI proctoring built in</h2>
            <p class="tf-reveal mb-4 text-[17px] text-[#3b82f6]" style="transition-delay:80ms">No downloads. No installs. One click to enable.</p>

            <div class="flex flex-col gap-6 md:flex-row md:items-start md:gap-8">
                <div class="flex-1 space-y-4">
                    @foreach([
                        ['ti-brand-google','Powered by MediaPipe','Uses Google\'s MediaPipe Face Landmarker to detect presence and count faces in-browser.'],
                        ['ti-eye','Camera feed analysis','The student\'s webcam is analyzed locally — the feed is never recorded or sent to the server.'],
                        ['ti-mouse','Mouse boundary detection','Detects when the cursor leaves the exam window, flagging potential second-screen use.'],
                        ['ti-browser-off','Tab switch detection','Any attempt to switch tabs or minimize the browser is logged as a violation immediately.'],
                    ] as $si => [$icon, $title, $desc])
                    <div class="tf-reveal flex gap-4" style="transition-delay:{{ $si * 70 }}ms">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#3b82f6]/15 mt-0.5">
                            <i class="ti {{ $icon }} text-[#3b82f6] text-[17px]"></i>
                        </div>
                        <div>
                            <p class="mb-0.5 text-[14px] font-[600] text-white">{{ $title }}</p>
                            <p class="text-[13px] leading-[1.55] text-white/55">{{ $desc }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="w-full md:w-56 shrink-0 rounded-xl border border-white/[0.08] bg-[#0d1b33] p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-white/30">Live Session</p>
                        <span class="flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] text-emerald-400"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Active</span>
                    </div>
                    <div class="mb-3 flex h-24 items-center justify-center rounded-xl border border-white/[0.07] bg-slate-800/60">
                        <div class="text-center">
                            <i class="ti ti-video text-slate-500 text-[24px] block mb-1"></i>
                            <span class="text-[10px] text-emerald-400">Face detected</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach([['Tab focus','Normal','text-emerald-400'],['Face','Clear','text-emerald-400'],['Tab switches','0','text-emerald-400'],['Warnings','2','text-amber-400']] as [$l,$v,$c])
                        <div class="rounded-lg bg-white/[0.04] px-3 py-2">
                            <p class="text-[9px] text-white/30">{{ $l }}</p>
                            <p class="text-[12px] font-semibold {{ $c }}">{{ $v }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- ════════════════════════════════════════════
             SECTION 11 — API
        ════════════════════════════════════════════ --}}
        <section id="api" data-tour-section="api" class="mb-10 scroll-mt-20">
            <p class="mb-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#3b82f6]">Developers</p>
            <h2 class="tf-reveal mb-1 text-[1.9rem] font-[700] leading-[1.15] text-white">ExamGuard API</h2>
            <p class="tf-reveal mb-4 text-[17px] text-[#3b82f6]" style="transition-delay:80ms">Integrate ExamGuard into your existing LMS.</p>

            <div class="flex flex-col gap-6 md:flex-row md:items-start md:gap-8">
                <div class="flex-1 space-y-4">
                    @foreach([
                        ['ti-key','REST JSON API','All core platform features are exposed via a clean REST API with JSON responses.'],
                        ['ti-webhook','Webhooks','Subscribe to exam completion and violation events and receive real-time POST callbacks.'],
                        ['ti-plug','LMS integration','Connect ExamGuard to Moodle, Canvas, or any custom LMS using standard OAuth2 flows.'],
                        ['ti-file-code','Full documentation','Comprehensive endpoint reference, code samples, and a Postman collection available on request.'],
                    ] as $si => [$icon, $title, $desc])
                    <div class="tf-reveal flex gap-4" style="transition-delay:{{ $si * 70 }}ms">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-[#3b82f6]/15 mt-0.5">
                            <i class="ti {{ $icon }} text-[#3b82f6] text-[17px]"></i>
                        </div>
                        <div>
                            <p class="mb-0.5 text-[14px] font-[600] text-white">{{ $title }}</p>
                            <p class="text-[13px] leading-[1.55] text-white/55">{{ $desc }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="w-full md:w-56 shrink-0 rounded-xl border border-white/[0.08] bg-[#162444] p-5">
                    <p class="mb-3 text-[10px] font-semibold uppercase tracking-wider text-white/30">API Request</p>
                    <div class="rounded-lg bg-[#0a1628] p-3 font-mono text-[11px] leading-[1.7]">
                        <span class="text-sky-400">GET</span> <span class="text-white/70">/api/v1/exams</span><br>
                        <span class="text-white/30">Authorization:</span> <span class="text-amber-300">Bearer {token}</span>
                    </div>
                    <div class="mt-2 rounded-lg bg-[#0a1628] p-3 font-mono text-[11px] leading-[1.7]">
                        <span class="text-white/30">{</span><br>
                        &nbsp;<span class="text-sky-400">"exams"</span><span class="text-white/30">: [</span><br>
                        &nbsp;&nbsp;<span class="text-white/30">{"id": 42,</span><br>
                        &nbsp;&nbsp;&nbsp;<span class="text-sky-400">"title"</span><span class="text-white/30">: </span><span class="text-emerald-400">"Midterm"</span><span class="text-white/30">}</span><br>
                        &nbsp;<span class="text-white/30">]</span><br>
                        <span class="text-white/30">}</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- ════════════════════════════════════════════
             SECTION 12 — CUSTOMERS
        ════════════════════════════════════════════ --}}
        <section id="customers" data-tour-section="customers" class="mb-10 scroll-mt-20">
            <p class="mb-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-[#3b82f6]">Community</p>
            <h2 class="tf-reveal mb-1 text-[1.9rem] font-[700] leading-[1.15] text-white">Trusted by educators worldwide</h2>
            <p class="tf-reveal mb-4 text-[17px] text-[#3b82f6]" style="transition-delay:80ms">Join thousands of professors already using ExamGuard.</p>

            <div class="tf-reveal mb-6 grid grid-cols-2 gap-3 text-center md:grid-cols-4" style="transition-delay:80ms">
                @foreach([['12k+','Students monitored'],['50+','Institutions'],['99%','Detection accuracy'],['3+','Years in service']] as [$n,$l])
                <div class="rounded-xl border border-white/[0.07] bg-[#162444] px-3 py-4">
                    <p class="text-[1.7rem] font-[800] leading-none text-[#3b82f6]">{{ $n }}</p>
                    <p class="mt-1.5 text-[12px] text-white/50">{{ $l }}</p>
                </div>
                @endforeach
            </div>

            <div class="tf-reveal flex flex-wrap items-center gap-x-7 gap-y-3" style="transition-delay:160ms">
                @foreach(['Universitas Teknologi','Mapúa MCL','AMA University','FEU Institute','DLSU Manila','NU Philippines','PUP Manila'] as $name)
                <span class="text-[13px] font-semibold tracking-wide text-white/30">{{ $name }}</span>
                @endforeach
            </div>
        </section>

    </main>
</div>
</div>

@include('partials.marketing-footer')
@endsection

@push('scripts')
<script src="/js/tour.js"></script>
@endpush
