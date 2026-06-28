@extends('layouts.auth')
@section('title', 'Sign In | ExamGuard')

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

        <div class="w-full max-w-sm">

            {{-- ══ FORM PANEL ══ --}}
            <div id="formPanel">

                <div class="mb-8">
                    <h2 class="text-[1.75rem] font-[700] text-slate-900">Sign in</h2>
                    <p class="mt-1 text-[14px] text-slate-500">Welcome back. Enter your credentials to continue.</p>
                </div>

                {{-- Verified success banner --}}
                <div id="verifiedBanner" class="hidden mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                    <div class="flex items-center gap-2.5">
                        <i class="ti ti-circle-check text-emerald-500 text-[17px] shrink-0"></i>
                        <p class="text-[13px] font-[500] text-emerald-700">Email verified! You can now sign in.</p>
                    </div>
                </div>

                <form id="loginForm" class="space-y-4" novalidate>
                    {{-- Honeypot: bots fill this; humans leave it empty --}}
                    <input type="text" name="website" id="hp_login" value="" autocomplete="off"
                           tabindex="-1" aria-hidden="true"
                           style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;opacity:0;pointer-events:none;">

                    <div>
                        <label for="emailInput" class="mb-1.5 block text-[13px] font-[500] text-slate-600">Email address</label>
                        <input id="emailInput" type="email" class="auth-input" placeholder="name@example.com" autocomplete="email">
                        <p id="emailMsg" class="field-msg err hidden"><i class="ti ti-alert-circle text-[12px]"></i><span></span></p>
                    </div>

                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label for="passwordInput" class="text-[13px] font-[500] text-slate-600">Password</label>
                            <a href="#" class="text-[12px] text-[#3b82f6] transition hover:text-blue-700">Forgot password?</a>
                        </div>
                        <div class="relative">
                            <input id="passwordInput" type="password" class="auth-input pr-11" placeholder="Enter your password" autocomplete="current-password">
                            <button type="button" id="togglePassword"
                                    class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-600"
                                    aria-label="Toggle password visibility">
                                <svg id="eyeIcon" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg id="eyeOffIcon" class="hidden h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.97 9.97 0 012.52-4.084M6.53 6.53A9.97 9.97 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-1.357 2.56M6.53 6.53L3 3m3.53 3.53l11.94 11.94M17.47 17.47L21 21"/>
                                </svg>
                            </button>
                        </div>
                        <p id="passwordMsg" class="field-msg err hidden"><i class="ti ti-alert-circle text-[12px]"></i><span></span></p>
                    </div>

                    <button id="loginBtn" type="submit"
                            class="mt-1 flex w-full items-center justify-center gap-2 rounded-full bg-[#3b82f6] px-6 py-3.5 text-[15px] font-[600] text-white transition hover:brightness-110 hover:scale-[1.01] disabled:opacity-60 disabled:cursor-not-allowed">
                        <i id="loginLockIcon" class="ti ti-lock hidden text-[15px]"></i>
                        <span id="loginBtnText">Log in</span>
                        <svg id="loginSpinner" class="hidden h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                    </button>

                </form>

                <p class="mt-6 text-center text-[13px] text-slate-400">
                    Don't have an account?
                    <a href="/register" class="ml-1 font-[600] text-[#3b82f6] transition hover:text-blue-700">Sign up free</a>
                </p>

            </div>{{-- /formPanel --}}

            {{-- ══ VERIFY PANEL (shown when login attempted on unverified account) ══ --}}
            <div id="verifyPanel" class="hidden text-center py-4">

                <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-[#3b82f6]/10">
                    <i class="ti ti-mail-opened text-[#3b82f6] text-[32px]"></i>
                </div>

                <h2 class="mb-2 text-[1.6rem] font-[700] text-slate-900">Activate your account</h2>
                <p class="mb-1 text-[14px] text-slate-500 leading-[1.65]">Your email hasn't been verified yet.<br>We've resent the activation link to</p>
                <p id="verifyEmail" class="mb-4 text-[15px] font-[600] text-slate-800 break-all"></p>
                <p class="text-[13px] text-slate-400 leading-[1.65]">
                    Check your inbox and click the link to activate your account.
                </p>

                <div class="my-6 border-t border-slate-100"></div>

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

                <button type="button" id="backToLogin"
                        class="mt-6 text-[13px] text-slate-400 hover:text-slate-600 transition flex items-center gap-1 mx-auto">
                    <i class="ti ti-arrow-left text-[13px]"></i> Back to sign in
                </button>

            </div>{{-- /verifyPanel --}}

            {{-- ══ LOCKOUT PANEL ══ --}}
            <div id="lockoutPanel" class="hidden text-center py-4">

                {{-- Icon --}}
                <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100">
                    <i class="ti ti-shield-lock text-slate-400 text-[26px]"></i>
                </div>

                {{-- Heading --}}
                <h2 class="mb-1.5 text-[1.6rem] font-[700] text-slate-900">Temporarily locked</h2>
                <p class="mb-7 text-[14px] text-slate-400 leading-[1.65]">
                    Too many failed attempts. Please wait and try again.
                </p>

                {{-- Timer --}}
                <div class="mb-7 flex flex-col items-center gap-1">
                    <span id="lockoutTimer"
                          class="text-[3.25rem] font-[800] tabular-nums leading-none tracking-tight text-[#3b82f6]">
                        --:--
                    </span>
                    <span class="text-[11px] font-[500] uppercase tracking-[0.12em] text-slate-300">remaining</span>
                </div>

                {{-- Divider --}}
                <div class="mb-5 border-t border-slate-100"></div>

                {{-- Footer note --}}
                <p class="text-[12.5px] text-slate-400 leading-[1.75]">
                    The sign-in form will unlock automatically.<br>
                    If this wasn't you, consider resetting your password.
                </p>

            </div>{{-- /lockoutPanel --}}

        </div>
    </div>

</div>
@endsection

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3/dist/tabler-icons.min.css">
<style>
    @keyframes panelFadeIn {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .panel-in { animation: panelFadeIn 0.35s ease-out both; }
</style>
@endpush

@push('scripts')
<script src="/js/api-client.js?v=3"></script>
<script src="/js/login.js?v=8"></script>
@endpush
