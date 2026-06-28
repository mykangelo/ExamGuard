@extends('layouts.marketing')
@section('title', 'Pricing | ExamGuard')

@section('content')
@include('partials.marketing-header', ['activePage' => 'pricing'])

{{-- Hero --}}
<section class="bg-[#0f1e3d] px-6 pb-24 pt-20 text-center md:pt-28">
    <div class="mx-auto max-w-2xl space-y-5">
        <p class="tf-reveal text-[12px] font-semibold uppercase tracking-[0.18em] text-[#3b82f6]">Pricing</p>
        <h1 class="tf-reveal mkt-display text-[3rem] font-bold leading-[1.05] text-white md:text-[4rem]"
            style="transition-delay: 80ms;">
            Simple, transparent pricing.
        </h1>
        <p class="tf-reveal text-[18px] leading-[1.7] text-white/60" style="transition-delay: 160ms;">
            Free for academic use. Upgrade for advanced analytics and institution-wide access.
        </p>
    </div>
</section>

{{-- Pricing cards --}}
<section class="border-t border-white/[0.07] bg-[#0f1e3d] px-6 py-24 md:py-32">
    <div class="mx-auto max-w-4xl">
        <div class="grid gap-5 md:grid-cols-3">

            {{-- Academic --}}
            <div class="tf-reveal flex flex-col rounded-2xl border border-white/[0.08] bg-[#162444] p-8">
                <div class="mb-6">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-white/40">Academic</p>
                    <div class="mt-3 font-[800] leading-none tracking-tight text-white"
                         style="font-size: 3rem; font-family: 'DM Sans', sans-serif;">Free</div>
                    <p class="mt-2 text-[15px] text-white/50">For individual professors and students.</p>
                </div>
                <ul class="mb-8 space-y-3 text-[15px] text-white/70">
                    @foreach(['Unlimited classes & exams','MCQ builder with auto-grading','Camera & tab monitoring','Violation logs per session','Up to 200 students','Email support'] as $item)
                    <li class="flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0 text-[#3b82f6]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ $item }}
                    </li>
                    @endforeach
                </ul>
                <a href="/login"
                   class="mt-auto block rounded-full border border-white/20 py-3 text-center text-[15px] font-semibold text-white/80 transition hover:border-white/40 hover:text-white">
                    Get started free
                </a>
            </div>

            {{-- Institution --}}
            <div class="tf-reveal relative flex flex-col rounded-2xl border border-[#3b82f6]/50 bg-[#162444] p-8"
                 style="transition-delay: 80ms;">
                <span class="absolute -top-4 left-1/2 -translate-x-1/2 rounded-full bg-[#3b82f6] px-4 py-1 text-[12px] font-semibold text-white">Most popular</span>
                <div class="mb-6">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#3b82f6]">Institution</p>
                    <div class="mt-3 font-[800] leading-none tracking-tight text-white"
                         style="font-size: 3rem; font-family: 'DM Sans', sans-serif;">Custom</div>
                    <p class="mt-2 text-[15px] text-white/50">For schools and universities.</p>
                </div>
                <ul class="mb-8 space-y-3 text-[15px] text-white/70">
                    @foreach(['Everything in Academic','Unlimited students','Advanced analytics & exports','Bulk exam assignment','Custom branding options','Priority support','Dedicated onboarding'] as $item)
                    <li class="flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0 text-[#3b82f6]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ $item }}
                    </li>
                    @endforeach
                </ul>
                <a href="/contact"
                   class="mt-auto block rounded-full bg-[#3b82f6] py-3 text-center text-[15px] font-semibold text-white transition hover:brightness-110">
                    Contact sales
                </a>
            </div>

            {{-- Enterprise --}}
            <div class="tf-reveal flex flex-col rounded-2xl border border-white/[0.08] bg-[#162444] p-8"
                 style="transition-delay: 160ms;">
                <div class="mb-6">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-white/40">Enterprise</p>
                    <div class="mt-3 font-[800] leading-none tracking-tight text-white"
                         style="font-size: 3rem; font-family: 'DM Sans', sans-serif;">Custom</div>
                    <p class="mt-2 text-[15px] text-white/50">For large networks and departments.</p>
                </div>
                <ul class="mb-8 space-y-3 text-[15px] text-white/70">
                    @foreach(['Everything in Institution','API & webhook access','SSO / SAML integration','SLA guarantee','Custom data retention','Dedicated account manager'] as $item)
                    <li class="flex items-center gap-3">
                        <svg class="h-4 w-4 shrink-0 text-[#3b82f6]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        {{ $item }}
                    </li>
                    @endforeach
                </ul>
                <a href="/contact"
                   class="mt-auto block rounded-full border border-white/20 py-3 text-center text-[15px] font-semibold text-white/80 transition hover:border-white/40 hover:text-white">
                    Talk to us
                </a>
            </div>

        </div>
    </div>
</section>

{{-- What's included --}}
<section class="border-t border-white/[0.07] bg-[#0f1e3d] px-6 py-24 md:py-32">
    <div class="mx-auto max-w-4xl">
        <div class="mb-16 text-center">
            <p class="tf-reveal mb-4 text-[12px] font-semibold uppercase tracking-[0.18em] text-[#3b82f6]">Included in every plan</p>
            <h2 class="tf-reveal text-[2.4rem] font-[700] leading-[1.1] tracking-[-0.02em] text-white"
                style="transition-delay: 80ms;">
                What's included in every plan
            </h2>
        </div>
        <div class="grid gap-x-12 gap-y-8 md:grid-cols-2">
            @foreach([
                ['MCQ exam builder','Create unlimited questions with up to 4 choices each.'],
                ['Auto-grading','Instant score calculation on submission.'],
                ['Camera monitoring','AI face detection via MediaPipe, browser-native.'],
                ['Violation logging','Timestamped record of every suspicious event.'],
                ['Class management','Organize students into classes with join codes.'],
                ['Student dashboard','Students see assigned exams and scores in one place.'],
                ['Role-based access','Separate professor and student interfaces.'],
                ['Secure sessions','CSRF protection, HttpOnly cookies, bcrypt passwords.'],
            ] as $i => [$title, $desc])
            <div class="tf-reveal flex items-start gap-4"
                 style="transition-delay: {{ ($i % 4) * 60 }}ms;">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#3b82f6]/15">
                    <svg class="h-5 w-5 text-[#3b82f6]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <p class="text-[17px] font-[600] text-white">{{ $title }}</p>
                    <p class="mt-1 text-[15px] text-white/55">{{ $desc }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="border-t border-white/[0.07] bg-[#0f1e3d] px-6 py-24 text-center">
    <div class="tf-reveal mx-auto max-w-xl space-y-6">
        <h2 class="text-[2.4rem] font-[700] leading-[1.1] tracking-[-0.02em] text-white">Questions about pricing?</h2>
        <p class="text-[17px] leading-[1.7] text-white/60">Check our FAQ or reach out — we're happy to help you find the right plan.</p>
        <div class="flex flex-wrap items-center justify-center gap-4 pt-2">
            <a href="/faq"
               class="inline-flex items-center justify-center rounded-full border border-white/20 px-8 py-3 text-[15px] font-medium text-white/70 transition hover:border-white/40 hover:text-white">
                View FAQ
            </a>
            <a href="/contact"
               class="inline-flex items-center justify-center rounded-full bg-white px-8 py-3 text-[15px] font-semibold text-[#0f1e3d] transition hover:scale-[1.03] hover:bg-white/90">
                Contact us
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
