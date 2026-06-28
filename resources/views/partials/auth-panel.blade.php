{{--
    auth-panel.blade.php — Left column redesigned slideshow for /login and /register.
    Horizontal slide carousel: caption fade + card scale-in on each transition.
--}}
<div class="hidden lg:flex lg:w-[44%] flex-col" id="authPanel"
     style="background: radial-gradient(ellipse at 45% 35%, #1a3060 0%, #0a1628 70%);">

    {{-- ── Top bar ── --}}
    <div class="flex shrink-0 items-center justify-between px-8 pt-7 pb-0">
        <a href="/" class="flex items-center gap-2">
            <img src="/images/logo.png" alt="examguard" class="h-9 w-auto object-contain drop-shadow">
            <span style="font-family:'Space Grotesk',sans-serif; font-size:18px; font-weight:600; color:#fff; letter-spacing:-0.4px;">examguard.</span>
        </a>
        <a href="/" class="auth-back-link flex items-center gap-1" style="font-size:13px; color:rgba(255,255,255,0.45);">
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Home
        </a>
    </div>

    {{-- ── Slide viewport (overflow:hidden clips the sliding track) ── --}}
    <div class="flex-1 min-h-0 overflow-hidden" id="slideViewport">
        <div id="slidesTrack" class="flex h-full"
             style="transition: transform 0.45s cubic-bezier(0.4,0,0.2,1); will-change: transform;">

            {{-- ══════════════════════════════════════
                 SLIDE 1 — Exam Builder
            ══════════════════════════════════════ --}}
            <div class="slide flex-none w-full h-full flex flex-col items-center justify-center px-8 gap-6">
                {{-- Caption --}}
                <p class="slide-caption text-center text-white/80 leading-relaxed max-w-[340px]"
                   style="font-size:16px; line-height:1.65;">
                    Explore the features that make exam monitoring effortless.
                </p>
                {{-- Mockup card --}}
                <div class="mockup-card w-full rounded-2xl overflow-hidden"
                     style="max-width:460px; background:#0d1b35; border:1px solid rgba(255,255,255,0.10);">
                    <div class="px-5 pt-4 pb-1">
                        {{-- Card header --}}
                        <div class="flex items-center justify-between mb-3">
                            <span style="font-size:9px; font-weight:700; letter-spacing:2px; color:rgba(255,255,255,0.28); text-transform:uppercase;">Exam Builder</span>
                            <span class="rounded-full px-2 py-0.5" style="font-size:9px; font-weight:600; color:#22c55e; background:rgba(34,197,94,0.12);">3 questions</span>
                        </div>
                        {{-- Exam title --}}
                        <div class="mb-1 pb-2.5" style="border-bottom:1px solid rgba(255,255,255,0.07);">
                            <p style="font-size:8px; color:rgba(255,255,255,0.22); margin-bottom:3px;">Title</p>
                            <p style="font-size:14px; font-weight:600; color:#fff;">Midterm Exam — CS101</p>
                        </div>
                        {{-- Question rows --}}
                        @foreach(['Q1: What is academic integrity?','Q2: Which API detects faces?','Q3: Exam time limit?'] as $q)
                        <div class="flex items-center justify-between py-2" style="border-bottom:1px solid rgba(255,255,255,0.05);">
                            <span style="font-size:12px; color:rgba(255,255,255,0.65);">{{ $q }}</span>
                            <span class="ml-3 shrink-0 rounded-full px-2 py-0.5"
                                  style="font-size:10px; font-weight:500; color:#3b82f6; background:rgba(59,130,246,0.15);">MCQ</span>
                        </div>
                        @endforeach
                        {{-- Add question row --}}
                        <div class="py-2.5 text-center" style="font-size:12px; color:rgba(255,255,255,0.28); border-top:1px dashed rgba(255,255,255,0.08);">
                            + Add question
                        </div>
                    </div>
                    {{-- Accent strip --}}
                    <div class="px-5 py-3.5" style="background:linear-gradient(to right,#16305a,#0e2048);">
                        <p style="font-size:9px; letter-spacing:2px; color:#3b82f6; margin-bottom:3px;">EXAM BUILDER</p>
                        <p style="font-size:14px; font-weight:600; color:#fff;">Build your exam in minutes.</p>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════
                 SLIDE 2 — Live Monitoring
            ══════════════════════════════════════ --}}
            <div class="slide flex-none w-full h-full flex flex-col items-center justify-center px-8 gap-6">
                <p class="slide-caption text-center text-white/80 leading-relaxed max-w-[340px]"
                   style="font-size:16px; line-height:1.65;">
                    Watch every exam in real time. Violations are flagged the moment they happen.
                </p>
                <div class="mockup-card w-full rounded-2xl overflow-hidden"
                     style="max-width:460px; background:#0d1b35; border:1px solid rgba(255,255,255,0.10);">
                    <div class="px-5 pt-4 pb-1">
                        <div class="flex items-center justify-between mb-3">
                            <span style="font-size:9px; font-weight:700; letter-spacing:2px; color:rgba(255,255,255,0.28); text-transform:uppercase;">Live Monitor</span>
                            <span class="flex items-center gap-1.5 rounded-full px-2.5 py-0.5"
                                  style="font-size:9px; font-weight:600; color:#22c55e; background:rgba(34,197,94,0.12);">
                                <span class="rounded-full animate-pulse" style="height:6px; width:6px; background:#22c55e; display:inline-block;"></span>
                                Active
                            </span>
                        </div>
                        {{-- Student rows --}}
                        @foreach([
                            ['Ana Reyes',  'Active',  '#22c55e', '14:32'],
                            ['Ben Torres', 'Flagged', '#ef4444', '14:31'],
                            ['Cara Lopez', 'Active',  '#22c55e', '14:30'],
                        ] as [$name, $status, $color, $time])
                        <div class="flex items-center justify-between py-2.5" style="border-bottom:1px solid rgba(255,255,255,0.05);">
                            <div class="flex items-center gap-2.5">
                                <div class="rounded-full flex items-center justify-center shrink-0"
                                     style="height:26px; width:26px; background:rgba(255,255,255,0.06); font-size:10px; font-weight:600; color:rgba(255,255,255,0.45);">
                                    {{ $name[0] }}
                                </div>
                                <span style="font-size:13px; color:rgba(255,255,255,0.72);">{{ $name }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="flex items-center gap-1.5" style="font-size:11px; color:{{ $color }};">
                                    <span class="rounded-full" style="height:6px; width:6px; background:{{ $color }}; display:inline-block;"></span>
                                    {{ $status }}
                                </span>
                                <span style="font-size:11px; color:rgba(255,255,255,0.20);">{{ $time }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="px-5 py-3.5" style="background:linear-gradient(to right,#16305a,#0e2048);">
                        <p style="font-size:9px; letter-spacing:2px; color:#3b82f6; margin-bottom:3px;">LIVE MONITOR</p>
                        <p style="font-size:14px; font-weight:600; color:#fff;">Zero violations go unnoticed.</p>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════
                 SLIDE 3 — Exam Results
            ══════════════════════════════════════ --}}
            <div class="slide flex-none w-full h-full flex flex-col items-center justify-center px-8 gap-6">
                <p class="slide-caption text-center text-white/80 leading-relaxed max-w-[340px]"
                   style="font-size:16px; line-height:1.65;">
                    Instant grading. Every score, every insight — available the moment exams close.
                </p>
                <div class="mockup-card w-full rounded-2xl overflow-hidden"
                     style="max-width:460px; background:#0d1b35; border:1px solid rgba(255,255,255,0.10);">
                    <div class="px-5 pt-4 pb-2">
                        <div class="flex items-center justify-between mb-3">
                            <span style="font-size:9px; font-weight:700; letter-spacing:2px; color:rgba(255,255,255,0.28); text-transform:uppercase;">Results</span>
                            <button class="rounded-md px-2.5 py-0.5" style="font-size:10px; color:rgba(255,255,255,0.35); border:1px solid rgba(255,255,255,0.12);">Export</button>
                        </div>
                        {{-- Score rows --}}
                        @foreach([
                            ['Ana Reyes',  '92%', 'Pass', '#22c55e', 'rgba(34,197,94,0.12)'],
                            ['Ben Torres', '61%', 'Fail', '#ef4444', 'rgba(239,68,68,0.12)'],
                            ['Cara Lopez', '88%', 'Pass', '#22c55e', 'rgba(34,197,94,0.12)'],
                        ] as [$name, $score, $verdict, $color, $bg])
                        <div class="flex items-center justify-between py-2.5" style="border-bottom:1px solid rgba(255,255,255,0.05);">
                            <span style="font-size:13px; color:rgba(255,255,255,0.72);">{{ $name }}</span>
                            <div class="flex items-center gap-3">
                                <span style="font-size:13px; font-weight:600; color:rgba(255,255,255,0.80);">{{ $score }}</span>
                                <span class="rounded-full px-2.5 py-0.5"
                                      style="font-size:10px; font-weight:500; color:{{ $color }}; background:{{ $bg }};">{{ $verdict }}</span>
                            </div>
                        </div>
                        @endforeach
                        {{-- Score distribution bar chart --}}
                        <div class="pt-3 pb-1">
                            <p style="font-size:8px; color:rgba(255,255,255,0.20); letter-spacing:2px; text-transform:uppercase; margin-bottom:8px;">Score distribution</p>
                            <div class="flex items-end gap-2" style="height:40px;">
                                @foreach([[35,'60–70'],[70,'71–80'],[100,'81–90'],[55,'91–100']] as [$pct, $label])
                                <div class="flex flex-col items-center gap-1 flex-1">
                                    <div class="w-full rounded-sm" style="height:{{ intval($pct * 0.34) }}px; background:rgba(59,130,246,0.38);"></div>
                                    <span style="font-size:7px; color:rgba(255,255,255,0.22);">{{ $label }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="px-5 py-3.5" style="background:linear-gradient(to right,#16305a,#0e2048);">
                        <p style="font-size:9px; letter-spacing:2px; color:#3b82f6; margin-bottom:3px;">EXAM RESULTS</p>
                        <p style="font-size:14px; font-weight:600; color:#fff;">Every result, delivered instantly.</p>
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════
                 SLIDE 4 — Certificates
            ══════════════════════════════════════ --}}
            <div class="slide flex-none w-full h-full flex flex-col items-center justify-center px-8 gap-6">
                <p class="slide-caption text-center text-white/80 leading-relaxed max-w-[340px]"
                   style="font-size:16px; line-height:1.65;">
                    Reward achievement automatically. Certificates issued the moment the pass mark is met.
                </p>
                <div class="mockup-card w-full rounded-2xl overflow-hidden"
                     style="max-width:460px; background:#0d1b35; border:1px solid rgba(255,255,255,0.10);">
                    <div class="px-5 pt-4 pb-2 flex justify-center">
                        {{-- Certificate preview box --}}
                        <div class="w-full rounded-xl px-6 py-5 text-center"
                             style="border:1.5px solid rgba(251,191,36,0.28); background:rgba(251,191,36,0.025);">
                            {{-- Corner ornaments --}}
                            <div class="flex justify-between mb-2" style="opacity:0.45;">
                                <span style="color:#f59e0b; font-size:15px;">✦</span>
                                <span style="color:#f59e0b; font-size:15px;">✦</span>
                            </div>
                            {{-- Logo --}}
                            <div class="flex justify-center mb-2">
                                <img src="/images/logo.png" alt="examguard" class="drop-shadow" style="height:26px; width:auto; opacity:0.65;">
                            </div>
                            <p style="font-size:8px; letter-spacing:3px; color:rgba(255,255,255,0.22); text-transform:uppercase; margin-bottom:6px;">examguard</p>
                            <p style="font-size:15px; font-weight:600; color:rgba(255,255,255,0.85); margin-bottom:6px;">Certificate of Completion</p>
                            <p style="font-size:10px; color:rgba(255,255,255,0.32); margin-bottom:8px;">This certifies that</p>
                            <p style="font-size:14px; font-weight:500; color:rgba(255,255,255,0.70); font-style:italic; margin-bottom:8px;">Juan dela Cruz</p>
                            <p style="font-size:10px; color:rgba(255,255,255,0.28); margin-bottom:6px;">has successfully completed</p>
                            <p style="font-size:12px; font-weight:500; color:rgba(255,255,255,0.58); margin-bottom:10px;">Midterm Exam — CS101</p>
                            {{-- Bottom ornaments --}}
                            <div class="flex justify-between mt-2 pt-3" style="opacity:0.38; border-top:1px solid rgba(251,191,36,0.20);">
                                <span style="color:#f59e0b; font-size:15px;">✦</span>
                                <span style="color:#f59e0b; font-size:15px;">✦</span>
                            </div>
                        </div>
                    </div>
                    <div class="px-5 py-3.5 mt-1" style="background:linear-gradient(to right,#16305a,#0e2048);">
                        <p style="font-size:9px; letter-spacing:2px; color:#3b82f6; margin-bottom:3px;">CERTIFICATES</p>
                        <p style="font-size:14px; font-weight:600; color:#fff;">Recognize achievement automatically.</p>
                    </div>
                </div>
            </div>

        </div>{{-- /slidesTrack --}}
    </div>

    {{-- ── Navigation controls ── --}}
    <div class="shrink-0 flex items-center justify-center gap-3 pt-4 pb-5 px-8">
        {{-- Prev --}}
        <button id="slidePrev" class="auth-nav-btn" aria-label="Previous">
            <svg class="h-3.5 w-3.5" style="color:#fff;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
        {{-- Play/Pause --}}
        <button id="slidePauseBtn" class="auth-nav-btn" aria-label="Pause">
            <svg id="pauseIcon" class="h-3 w-3" style="color:#fff;" fill="currentColor" viewBox="0 0 24 24">
                <rect x="6" y="4" width="4" height="16" rx="1"/><rect x="14" y="4" width="4" height="16" rx="1"/>
            </svg>
            <svg id="playIcon" class="hidden h-3 w-3" style="color:#fff;" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z"/>
            </svg>
        </button>
        {{-- Dot indicators --}}
        <div class="flex items-center gap-1.5" id="slideDots">
            @for ($i = 0; $i < 4; $i++)
            <button class="slide-dot rounded-full" data-dot="{{ $i }}"
                    style="height:8px; transition: width 0.3s ease, background 0.3s ease;
                           {{ $i === 0 ? 'width:24px; border-radius:4px; background:rgba(255,255,255,1);'
                                       : 'width:8px; background:rgba(255,255,255,0.25);' }}">
            </button>
            @endfor
        </div>
        {{-- Next --}}
        <button id="slideNext" class="auth-nav-btn" aria-label="Next">
            <svg class="h-3.5 w-3.5" style="color:#fff;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>

    {{-- Copyright --}}
    <p class="shrink-0 px-8 pb-6" style="font-size:11px; color:rgba(255,255,255,0.28);">© {{ date('Y') }} examguard</p>

</div>

@push('scripts')
<script>
(function () {
    const track    = document.getElementById('slidesTrack');
    const slides   = Array.from(track.querySelectorAll('.slide'));
    const dots     = document.querySelectorAll('.slide-dot');
    const prevBtn  = document.getElementById('slidePrev');
    const nextBtn  = document.getElementById('slideNext');
    const pauseBtn = document.getElementById('slidePauseBtn');
    const pauseIco = document.getElementById('pauseIcon');
    const playIco  = document.getElementById('playIcon');
    const panel    = document.getElementById('authPanel');

    const TOTAL = slides.length;
    const DELAY = 4000;
    let current = 0;
    let paused  = false;
    let timer   = null;

    /* Replay a CSS animation by briefly clearing and reapplying it */
    function replayAnim(el, anim) {
        el.style.animation = 'none';
        void el.offsetWidth; // force reflow
        el.style.animation = anim;
    }

    function animateSlideIn(slideEl) {
        const caption = slideEl.querySelector('.slide-caption');
        const card    = slideEl.querySelector('.mockup-card');
        if (caption) replayAnim(caption, 'authCaptionIn 0.40s 0.22s ease-out both');
        if (card)    replayAnim(card,    'authCardIn    0.45s          ease-out both');
    }

    function goTo(idx) {
        current = (idx + TOTAL) % TOTAL;
        track.style.transform = `translateX(-${current * 100}%)`;

        /* Update dot indicators */
        dots.forEach((d, i) => {
            if (i === current) {
                d.style.width      = '24px';
                d.style.borderRadius = '4px';
                d.style.background = 'rgba(255,255,255,1)';
            } else {
                d.style.width      = '8px';
                d.style.borderRadius = '50%';
                d.style.background = 'rgba(255,255,255,0.25)';
            }
        });

        animateSlideIn(slides[current]);
    }

    function startTimer() { clearInterval(timer); timer = setInterval(() => goTo(current + 1), DELAY); }
    function stopTimer()  { clearInterval(timer); timer = null; }

    prevBtn.addEventListener('click', () => { goTo(current - 1); if (!paused) { stopTimer(); startTimer(); } });
    nextBtn.addEventListener('click', () => { goTo(current + 1); if (!paused) { stopTimer(); startTimer(); } });

    pauseBtn.addEventListener('click', () => {
        paused = !paused;
        if (paused) { stopTimer(); pauseIco.classList.add('hidden'); playIco.classList.remove('hidden'); }
        else        { startTimer(); playIco.classList.add('hidden'); pauseIco.classList.remove('hidden'); }
    });

    dots.forEach((dot, i) => dot.addEventListener('click', () => {
        goTo(i); if (!paused) { stopTimer(); startTimer(); }
    }));

    panel.addEventListener('mouseenter', () => { if (!paused) stopTimer(); });
    panel.addEventListener('mouseleave', () => { if (!paused) startTimer(); });

    /* Boot */
    animateSlideIn(slides[0]);
    startTimer();
})();
</script>
@endpush
