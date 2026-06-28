@extends('layouts.auth')
@section('title', 'Sign Up | ExamGuard')

@section('content')
<div class="flex min-h-screen">

    @include('partials.auth-panel')

    {{-- ══ RIGHT PANEL — form ══ --}}
    <div class="flex flex-1 flex-col items-center justify-center px-6 py-12"
         style="background: linear-gradient(to right,
                    #0a1628 0%,
                    #1d3a60 4%,
                    #4a7299 9%,
                    #9ec4d8 15%,
                    #d4eaf5 21%,
                    #edf5fb 26%,
                    #f8fafc 32%);">

        {{-- Mobile logo --}}
        <div class="mb-8 flex items-center gap-2 lg:hidden">
            <img src="/images/logo.png" alt="examguard" class="h-10 w-auto object-contain">
            <span class="text-slate-800" style="font-family:'Space Grotesk',sans-serif; font-size:20px; font-weight:600; letter-spacing:-0.4px;">examguard.</span>
        </div>

        <div class="w-full max-w-sm relative overflow-hidden">

            {{-- ══ FORM PANEL ══ --}}
            <div id="formPanel">

                <div class="mb-6">
                    <h2 class="text-[1.75rem] font-[700] text-slate-900">Create your account</h2>
                    <p class="mt-1 text-[14px] text-slate-500">Free forever. No credit card required.</p>
                </div>

                {{-- ── Role selector ── --}}
                <div class="mb-5">
                    <p class="mb-2 text-[12px] font-[500] uppercase tracking-wider text-slate-400">I am a</p>
                    <div class="flex gap-3">
                        <button type="button" id="roleProf"
                                class="role-btn flex flex-1 items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-4 py-3 text-[14px] text-slate-500 transition-all duration-150 hover:border-blue-300 hover:text-slate-800"
                                data-role="professor">
                            <i class="ti ti-chalkboard text-[18px] shrink-0"></i>
                            <span class="font-[500]">Professor</span>
                        </button>
                        <button type="button" id="roleStud"
                                class="role-btn flex flex-1 items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-4 py-3 text-[14px] text-slate-500 transition-all duration-150 hover:border-blue-300 hover:text-slate-800"
                                data-role="student">
                            <i class="ti ti-school text-[18px] shrink-0"></i>
                            <span class="font-[500]">Student</span>
                        </button>
                    </div>
                    <p id="roleMsg" class="field-msg err hidden mt-1.5"><i class="ti ti-alert-circle text-[12px]"></i><span>Please select a role to continue.</span></p>
                </div>

                {{-- Error banner --}}
                <div id="registerError" class="hidden mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                    <div class="flex items-start gap-2.5">
                        <i class="ti ti-alert-circle text-red-500 text-[16px] shrink-0 mt-[1px]"></i>
                        <div class="flex-1 min-w-0">
                            <p class="text-[13px] font-[500] text-red-700" id="registerErrorText"></p>
                        </div>
                        <button type="button" id="registerErrorClose" class="shrink-0 text-red-400 hover:text-red-600 transition ml-1" aria-label="Dismiss">
                            <i class="ti ti-x text-[13px]"></i>
                        </button>
                    </div>
                </div>

                <form id="registerForm" class="space-y-4" novalidate>
                    {{-- Honeypot: bots fill this; humans leave it empty --}}
                    <input type="text" name="website" id="hp_register" value="" autocomplete="off"
                           tabindex="-1" aria-hidden="true"
                           style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;opacity:0;pointer-events:none;">
                    <input type="hidden" id="selectedRole" value="">

                    <div>
                        <label for="nameInput" class="mb-1.5 block text-[13px] font-[500] text-slate-600">Full name</label>
                        <input id="nameInput" type="text" class="auth-input" placeholder="Juan dela Cruz" autocomplete="name">
                        <p id="nameMsg" class="field-msg err hidden"><i class="ti ti-alert-circle text-[12px]"></i><span></span></p>
                    </div>

                    <div>
                        <label for="emailInput" class="mb-1.5 block text-[13px] font-[500] text-slate-600">Email address</label>
                        <input id="emailInput" type="email" class="auth-input" placeholder="name@example.com" autocomplete="email">
                        <p id="emailMsg" class="field-msg err hidden"><i class="ti ti-alert-circle text-[12px]"></i><span></span></p>
                    </div>

                    <div>
                        <label for="passwordInput" class="mb-1.5 block text-[13px] font-[500] text-slate-600">Password</label>
                        <div class="relative">
                            <input id="passwordInput" type="password" class="auth-input pr-11" placeholder="Create a password" autocomplete="new-password">
                            <button type="button" id="togglePassword"
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600"
                                    aria-label="Toggle password">
                                <svg id="eyeIcon" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg id="eyeOffIcon" class="hidden h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.52-4.084M6.53 6.53A9.97 9.97 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-1.357 2.56M6.53 6.53L3 3m3.53 3.53l11.94 11.94M17.47 17.47L21 21"/>
                                </svg>
                            </button>
                        </div>
                        {{-- Strength meter --}}
                        <div id="strengthWrap" class="hidden mt-2">
                            <div class="strength-track">
                                <div id="strengthFill" class="strength-fill"></div>
                            </div>
                            <div class="mt-1.5 flex items-center justify-between">
                                <p id="strengthLabel" class="text-[11px] text-slate-400"></p>
                                <ul class="flex items-center gap-2">
                                    <li id="req-len"   class="flex items-center gap-1 text-[10px] text-slate-300"><span class="req-dot h-1.5 w-1.5 rounded-full bg-slate-300 shrink-0"></span>8+ chars</li>
                                    <li id="req-upper" class="flex items-center gap-1 text-[10px] text-slate-300"><span class="req-dot h-1.5 w-1.5 rounded-full bg-slate-300 shrink-0"></span>A–Z</li>
                                    <li id="req-num"   class="flex items-center gap-1 text-[10px] text-slate-300"><span class="req-dot h-1.5 w-1.5 rounded-full bg-slate-300 shrink-0"></span>0–9</li>
                                    <li id="req-sym"   class="flex items-center gap-1 text-[10px] text-slate-300"><span class="req-dot h-1.5 w-1.5 rounded-full bg-slate-300 shrink-0"></span>#@!</li>
                                </ul>
                            </div>
                        </div>
                        <p id="passwordMsg" class="field-msg err hidden"><i class="ti ti-alert-circle text-[12px]"></i><span></span></p>
                    </div>

                    <div>
                        <label for="passwordConfirmInput" class="mb-1.5 block text-[13px] font-[500] text-slate-600">Confirm password</label>
                        <input id="passwordConfirmInput" type="password" class="auth-input" placeholder="Repeat your password" autocomplete="new-password">
                        <p id="confirmMsg" class="field-msg hidden"><i class="ti ti-alert-circle text-[12px]"></i><span></span></p>
                    </div>

                    <button id="registerBtn" type="submit"
                            class="mt-1 flex w-full items-center justify-center gap-2.5 rounded-full bg-[#3b82f6] px-6 py-3.5 text-[15px] font-[600] text-white transition hover:brightness-110 hover:scale-[1.01] disabled:opacity-60 disabled:cursor-not-allowed">
                        <span id="registerBtnText">Create account</span>
                        <svg id="registerSpinner" class="hidden h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                    </button>
                </form>

                <p class="mt-6 text-center text-[13px] text-slate-400">
                    Already have an account?
                    <a href="/login" class="ml-1 font-[600] text-[#3b82f6] transition hover:text-blue-700">Sign in</a>
                </p>

            </div>{{-- /formPanel --}}

            {{-- ══ VERIFY PANEL (hidden until account created) ══ --}}
            <div id="verifyPanel" class="hidden text-center py-4">

                <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-[#3b82f6]/10">
                    <i class="ti ti-mail-opened text-[#3b82f6] text-[32px]"></i>
                </div>

                <h2 class="mb-2 text-[1.6rem] font-[700] text-slate-900">Activate your account</h2>
                <p class="mb-1 text-[14px] text-slate-500 leading-[1.65]">We've sent a verification email to</p>
                <p id="verifyEmail" class="mb-4 text-[15px] font-[600] text-slate-800 break-all"></p>
                <p class="text-[13px] text-slate-400 leading-[1.65]">
                    Click the link in the email to activate your account.<br>The link expires in <strong class="text-slate-600">60 minutes</strong>.
                </p>

                <div class="my-6 border-t border-slate-100"></div>

                {{-- Resent confirmation --}}
                <div id="resentBanner" class="hidden mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <div class="flex items-center justify-center gap-2">
                        <i class="ti ti-circle-check text-emerald-500 text-[15px]"></i>
                        <p class="text-[13px] font-[500] text-emerald-700">Verification email resent!</p>
                    </div>
                </div>

                <p class="text-[13px] text-slate-400">
                    Can't find it? Check your spam folder or
                    <button id="resendBtn" type="button"
                            class="font-[600] text-[#3b82f6] underline underline-offset-2 transition hover:text-blue-700 disabled:opacity-40 disabled:cursor-not-allowed">
                        resend
                    </button>.
                </p>

                <p class="mt-6 text-[13px] text-slate-400">
                    Already activated?
                    <a href="/login" class="ml-1 font-[600] text-[#3b82f6] transition hover:text-blue-700">Sign in</a>
                </p>

            </div>{{-- /verifyPanel --}}

        </div>
    </div>

</div>
@endsection

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3/dist/tabler-icons.min.css">
<style>
    .role-btn.selected {
        border-color: #3b82f6;
        background: rgba(59, 130, 246, 0.08);
        color: #1e40af;
    }
    .role-btn.selected i { color: #3b82f6; }
    .role-btn.selected span { color: #1e40af; }

    @keyframes panelFadeIn {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .panel-in { animation: panelFadeIn 0.35s ease-out both; }
</style>
@endpush

@push('scripts')
<script src="/js/api-client.js?v=3"></script>
<script src="/js/register.js?v=3"></script>
@endpush
