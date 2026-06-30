@php $activePage = $activePage ?? ''; @endphp

{{-- ── SINGLE STICKY NAV BAR (Typeform-style) ── --}}
<header class="mkt-site-header sticky top-0 z-50 bg-[#0f1e3d]">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-6 py-4">

        {{-- ── Left: Logo ── --}}
        <a href="/" class="flex shrink-0 items-center gap-2.5 text-white transition hover:opacity-90"
           style="font-family:'Space Grotesk',sans-serif; font-size:20px; font-weight:600; letter-spacing:-0.4px;">
            <img src="/images/logo.png" alt="examguard"
                 class="h-11 w-auto object-contain drop-shadow-[0_0_6px_rgba(59,130,246,0.5)]">
            examguard.
        </a>

        {{-- ── Center: Nav links — desktop ── --}}
        <nav class="hidden flex-1 items-center justify-center gap-1 lg:flex">

        {{-- Take a Tour dropdown — CSS hover --}}
        <div class="group/tour relative">
            <button class="flex items-center gap-1 rounded-lg px-4 py-2 text-[15px] transition {{ $activePage === 'tour' ? 'font-semibold text-white' : 'text-white/70 hover:text-white' }}">
                Take a Tour
                <svg class="h-3.5 w-3.5 transition-transform duration-200 group-hover/tour:rotate-180"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div class="pointer-events-none absolute left-0 top-full z-50 w-56 -translate-y-2 rounded-xl border border-white/10 bg-[#162444] py-2 opacity-0 shadow-2xl shadow-black/50 transition-all duration-200 group-hover/tour:pointer-events-auto group-hover/tour:translate-y-0 group-hover/tour:opacity-100">
                    @foreach([
                        ['How ExamGuard works', '/tour#overview'],
                        ['Features',            '/tour#features'],
                        ['Try demo exams',      '/login'],
                        ['Creating exams',      '/tour#exam-builder'],
                        ['Giving exams',        '/tour#professor-flow'],
                        ['Taking exams',        '/tour#student-flow'],
                        ['Exam results',        '/tour#violation-reports'],
                        ['Violations',          '/tour#monitoring'],
                        ['Certificates',        '/tour#instructions'],
                        ['ExamGuard Monitor',   '/#proctoring'],
                        ['API',                 '/tour#features'],
                        ['Our customers',       '/#trust'],
                    ] as [$label, $href])
                    <a href="{{ $href }}"
                       class="block px-4 py-2.5 text-[14px] text-white/60 transition hover:bg-white/5 hover:text-white">
                        {{ $label }}
                    </a>
                    @endforeach
                </div>
            </div>

            <a href="/pricing"
               class="rounded-lg px-4 py-2 text-[15px] transition {{ $activePage === 'pricing' ? 'font-semibold text-white' : 'text-white/70 hover:text-white' }}">
                Pricing
            </a>
            <a href="/faq"
               class="rounded-lg px-4 py-2 text-[15px] transition {{ $activePage === 'faq' ? 'font-semibold text-white' : 'text-white/70 hover:text-white' }}">
                FAQ
            </a>
            <a href="/contact"
               class="rounded-lg px-4 py-2 text-[15px] transition {{ $activePage === 'contact' ? 'font-semibold text-white' : 'text-white/70 hover:text-white' }}">
                Contact Us
            </a>
        </nav>

        {{-- ── Right: Log in + Sign up — desktop ── --}}
        <div class="hidden shrink-0 items-center gap-2 lg:flex">
            <a href="/login"
               class="rounded-lg px-4 py-2 text-[15px] text-white/70 transition hover:text-white">
                Log in
            </a>
            <a href="/register"
               class="rounded-full bg-white px-6 py-2.5 text-[14px] font-semibold text-[#0f1e3d] transition hover:scale-[1.03] hover:bg-white/90">
                Sign up
            </a>
        </div>

        {{-- ── Mobile: Log in + Hamburger ── --}}
        <div class="flex items-center gap-3 lg:hidden">
            <a href="/login"
               class="rounded-full bg-white px-4 py-2 text-[13px] font-semibold text-[#0f1e3d]">
                Log in
            </a>
            <button id="mobileNavBtn"
                    type="button"
                    class="flex h-11 w-11 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/5 hover:text-white"
                    aria-label="Open menu"
                    aria-expanded="false"
                    aria-controls="mobileNav">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- ── Mobile nav drawer ── --}}
    <div id="mobileNav" class="hidden border-t border-white/[0.07] bg-[#0f1e3d] px-5 py-3 lg:hidden">
        @foreach([
            ['/', 'Home', 'home'],
            ['/tour', 'Take a Tour', 'tour'],
            ['/pricing', 'Pricing', 'pricing'],
            ['/faq', 'FAQ', 'faq'],
            ['/contact', 'Contact Us', 'contact'],
        ] as [$href, $label, $page])
        <a href="{{ $href }}"
           class="block rounded-lg px-3 py-2.5 text-[14px] transition {{ $activePage === $page ? 'font-semibold text-white' : 'text-white/60 hover:bg-white/5 hover:text-white' }}">
            {{ $label }}
        </a>
        @endforeach
        <div class="mt-3 border-t border-white/[0.07] pt-3">
            <a href="/register"
               class="block rounded-lg px-3 py-2.5 text-[14px] text-white/60 transition hover:bg-white/5 hover:text-white">
                Sign up for free
            </a>
        </div>
    </div>
</header>
