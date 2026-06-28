@extends('layouts.marketing')
@section('title', 'Contact Us | ExamGuard')

@section('content')
@include('partials.marketing-header', ['activePage' => 'contact'])

{{-- Hero --}}
<section class="bg-[#0f1e3d] px-6 pb-24 pt-20 text-center md:pt-28">
    <div class="mx-auto max-w-xl space-y-5">
        <p class="tf-reveal text-[12px] font-semibold uppercase tracking-[0.18em] text-[#3b82f6]">Get in touch</p>
        <h1 class="tf-reveal mkt-display text-[3rem] font-bold leading-[1.05] text-white md:text-[4rem]"
            style="transition-delay: 80ms;">
            Contact us
        </h1>
        <p class="tf-reveal text-[18px] leading-[1.7] text-white/60" style="transition-delay: 160ms;">
            Have a question, need a demo, or want to discuss a plan for your institution?
        </p>
    </div>
</section>

{{-- Form + info --}}
<section class="border-t border-white/[0.07] bg-[#0f1e3d] px-6 py-24 md:py-32">
    <div class="mx-auto grid max-w-5xl gap-12 md:grid-cols-2">

        {{-- Form --}}
        <div class="tf-reveal">
            <h2 class="mb-8 text-[1.8rem] font-[700] tracking-[-0.015em] text-white">Send us a message</h2>
            <form id="contactForm" class="space-y-5">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-[14px] font-[600] text-white/60">Full name</label>
                        <input type="text"
                               class="w-full rounded-xl border border-white/[0.08] bg-[#162444] px-4 py-3 text-[16px] text-white outline-none transition placeholder:text-white/25 focus:border-[#3b82f6]/50 focus:ring-2 focus:ring-[#3b82f6]/20"
                               placeholder="Your name">
                    </div>
                    <div>
                        <label class="mb-2 block text-[14px] font-[600] text-white/60">Email address</label>
                        <input type="email"
                               class="w-full rounded-xl border border-white/[0.08] bg-[#162444] px-4 py-3 text-[16px] text-white outline-none transition placeholder:text-white/25 focus:border-[#3b82f6]/50 focus:ring-2 focus:ring-[#3b82f6]/20"
                               placeholder="your@email.com">
                    </div>
                </div>
                <div>
                    <label class="mb-2 block text-[14px] font-[600] text-white/60">Subject</label>
                    <select class="w-full rounded-xl border border-white/[0.08] bg-[#162444] px-4 py-3 text-[16px] text-white outline-none transition focus:border-[#3b82f6]/50 focus:ring-2 focus:ring-[#3b82f6]/20">
                        <option value="" class="bg-[#162444]">Select a topic</option>
                        <option class="bg-[#162444]">General inquiry</option>
                        <option class="bg-[#162444]">Institution / sales</option>
                        <option class="bg-[#162444]">Technical support</option>
                        <option class="bg-[#162444]">Feature request</option>
                        <option class="bg-[#162444]">Partnership</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-[14px] font-[600] text-white/60">Message</label>
                    <textarea class="w-full resize-none rounded-xl border border-white/[0.08] bg-[#162444] px-4 py-3 text-[16px] text-white outline-none transition placeholder:text-white/25 focus:border-[#3b82f6]/50 focus:ring-2 focus:ring-[#3b82f6]/20 min-h-36"
                              placeholder="How can we help?"></textarea>
                </div>
                <button type="submit"
                        class="w-full rounded-full bg-white py-4 text-[16px] font-semibold text-[#0f1e3d] transition hover:scale-[1.02] hover:bg-white/90">
                    Send message
                </button>
            </form>
        </div>

        {{-- Info --}}
        <div class="tf-reveal space-y-8" style="transition-delay: 120ms;">
            <h2 class="text-[1.8rem] font-[700] tracking-[-0.015em] text-white">Other ways to reach us</h2>

            <div class="space-y-3">
                @foreach([
                    ['M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z','Email','support@examguard.app','mailto:support@examguard.app'],
                    ['M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z','FAQ','Browse our frequently asked questions.','/faq'],
                    ['M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z','Documentation','Step-by-step setup and feature guides.','/tour'],
                ] as [$icon, $label, $desc, $href])
                <a href="{{ $href }}"
                   class="flex items-start gap-4 rounded-xl border border-white/[0.08] bg-[#162444] px-5 py-4 transition hover:border-[#3b82f6]/30 hover:bg-[#3b82f6]/5">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#3b82f6]/15">
                        <svg class="h-5 w-5 text-[#3b82f6]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
                    </div>
                    <div>
                        <p class="text-[16px] font-[600] text-white">{{ $label }}</p>
                        <p class="text-[14px] text-white/50">{{ $desc }}</p>
                    </div>
                </a>
                @endforeach
            </div>

            <div class="rounded-xl border border-white/[0.08] bg-[#162444] p-6">
                <h3 class="mb-2 text-[16px] font-[600] text-white">Response time</h3>
                <p class="text-[15px] leading-[1.7] text-white/55">We aim to respond to all messages within 1–2 business days. For urgent issues, include "URGENT" in the subject line.</p>
            </div>
        </div>

    </div>
</section>

@include('partials.marketing-footer')

@endsection
@push('scripts')
<script src="/js/api-client.js"></script>
<script src="/js/home.js"></script>
@endpush
