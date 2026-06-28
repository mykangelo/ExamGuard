@extends('layouts.marketing')
@section('title', 'FAQ | ExamGuard')

@section('content')
@include('partials.marketing-header', ['activePage' => 'faq'])

{{-- Hero --}}
<section class="bg-[#0f1e3d] px-6 pb-24 pt-20 text-center md:pt-28">
    <div class="mx-auto max-w-2xl space-y-5">
        <p class="tf-reveal text-[12px] font-semibold uppercase tracking-[0.18em] text-[#3b82f6]">Support</p>
        <h1 class="tf-reveal mkt-display text-[3rem] font-bold leading-[1.05] text-white md:text-[4rem]"
            style="transition-delay: 80ms;">
            Frequently asked questions
        </h1>
        <p class="tf-reveal text-[18px] leading-[1.7] text-white/60" style="transition-delay: 160ms;">
            Everything you need to know about ExamGuard.
        </p>
    </div>
</section>

{{-- FAQ sections --}}
<section class="border-t border-white/[0.07] bg-[#0f1e3d] px-6 py-24 md:py-32">
    <div class="mx-auto max-w-3xl">
        @php
        $sections = [
            ['Getting started', [
                ['What is ExamGuard?', 'ExamGuard is an online exam administration and monitoring platform for academic institutions. Professors create MCQ exams, assign them to classes, and monitor student sessions via webcam and tab-switch detection.'],
                ['Is there any software to install?', 'No. ExamGuard runs entirely in the web browser. Students only need to grant webcam permission when entering an exam session — no extensions, plugins, or downloads are required.'],
                ['How do I create an account?', 'Click "Sign up" in the header or "Get started" on the homepage. Professors register directly; students join an existing class using a 6-character code provided by their professor.'],
                ['What browsers are supported?', 'ExamGuard works best on modern Chromium-based browsers (Google Chrome, Microsoft Edge). Firefox and Safari are supported but camera features perform best on Chrome.'],
            ]],
            ['Exams & monitoring', [
                ['How does the camera monitoring work?', 'ExamGuard uses Google\'s MediaPipe Face Landmarker model to analyze the webcam feed locally in the browser. A warning is triggered and logged when no face or multiple faces are detected for more than 6 seconds.'],
                ['What violations does ExamGuard detect?', 'ExamGuard detects three types: (1) No face in frame for 6+ seconds, (2) Multiple faces detected simultaneously, (3) Tab switches or window focus loss. All events are timestamped and stored per session.'],
                ['Can students retake an exam?', 'No. Each student may submit once. Completed attempts are locked. Professors cannot currently reset individual attempts through the UI.'],
                ['What happens if the timer runs out?', 'The exam auto-submits when the timer reaches zero, recording whichever answers the student had selected at that point.'],
            ]],
            ['Security & privacy', [
                ['Is student data secure?', 'Yes. Passwords are hashed with bcrypt. Sessions use CSRF-protected, HttpOnly, SameSite-Strict cookies. The camera feed is processed locally in the browser and is never recorded or transmitted to the server.'],
                ['Who can see violation reports?', 'Only the professor who owns the exam can view violation logs. Students see only their score — not the violation details.'],
                ['Is ExamGuard GDPR compliant?', 'ExamGuard is designed with data minimization in mind. Camera data is processed locally and never leaves the student\'s device. Contact us for data protection documentation.'],
            ]],
            ['Classes & students', [
                ['How do students join a class?', 'Professors generate a 6-character join code when creating a class. Students enter this code on their dashboard to enroll.'],
                ['Can a professor have multiple classes?', 'Yes. Professors can create as many classes as needed and assign different exams to each.'],
                ['What is the student limit per class?', 'The Academic (free) plan supports up to 200 students. Institution and Enterprise plans have no fixed limit.'],
            ]],
        ];
        @endphp

        <div class="space-y-16">
            @foreach($sections as $sIdx => [$category, $faqs])
            <div class="tf-reveal" style="transition-delay: {{ $sIdx * 70 }}ms;">
                <h2 class="mb-6 text-[1.5rem] font-[700] tracking-[-0.01em] text-white">{{ $category }}</h2>
                <div class="space-y-2">
                    @foreach($faqs as [$q, $a])
                    <div class="faq-item overflow-hidden rounded-xl border border-white/[0.08] bg-[#162444]">
                        <button class="faq-trigger flex w-full items-center justify-between px-6 py-5 text-left text-[17px] font-[600] text-white transition hover:bg-white/[0.03]">
                            {{ $q }}
                            <svg class="faq-icon ml-4 h-5 w-5 shrink-0 text-white/30 transition-transform duration-200"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div class="faq-body hidden border-t border-white/[0.07] px-6 py-5 text-[16px] leading-[1.75] text-white/55">
                            {{ $a }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Still have questions? --}}
<section class="border-t border-white/[0.07] bg-[#0f1e3d] px-6 py-24 text-center">
    <div class="tf-reveal mx-auto max-w-xl space-y-6">
        <h2 class="text-[2.4rem] font-[700] leading-[1.1] tracking-[-0.02em] text-white">Still have questions?</h2>
        <p class="text-[17px] leading-[1.7] text-white/60">
            Can't find what you're looking for? Send us a message and we'll get back to you promptly.
        </p>
        <a href="/contact"
           class="inline-flex items-center justify-center rounded-full bg-white px-10 py-4 text-[16px] font-semibold text-[#0f1e3d] transition hover:scale-[1.03] hover:bg-white/90">
            Contact us
        </a>
    </div>
</section>

@include('partials.marketing-footer')

@endsection
@push('scripts')
<script src="/js/api-client.js"></script>
<script src="/js/home.js"></script>
@endpush
