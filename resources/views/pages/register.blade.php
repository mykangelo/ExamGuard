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
            <img src="/images/logo.png" alt="ExamGuard" class="h-8 w-8">
            <span class="text-[18px] font-[700] text-slate-800">ExamGuard</span>
        </div>

        <div class="w-full max-w-sm">

            <div class="mb-6">
                <h2 class="text-[1.75rem] font-[700] text-slate-900">Create your account</h2>
                <p class="mt-1 text-[14px] text-slate-500">Free forever. No credit card required.</p>
            </div>

            {{-- ── Role selector ── --}}
            <div class="mb-6">
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
            </div>

            {{-- Error / Success --}}
            <div id="registerError" class="hidden mb-4 flex items-center gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-[13px] text-red-600">
                <i class="ti ti-alert-circle text-[16px] shrink-0"></i>
                <span id="registerErrorText"></span>
            </div>
            <div id="registerSuccess" class="hidden mb-4 flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[13px] text-emerald-700">
                <i class="ti ti-circle-check text-[16px] shrink-0"></i>
                <span>Account created! Redirecting…</span>
            </div>

            <form id="registerForm" class="space-y-4" novalidate>
                <input type="hidden" id="selectedRole" value="">

                <div>
                    <label for="nameInput" class="mb-1.5 block text-[13px] font-[500] text-slate-600">Full name</label>
                    <input id="nameInput" type="text" class="auth-input" placeholder="Juan dela Cruz" autocomplete="name" required>
                </div>

                <div>
                    <label for="emailInput" class="mb-1.5 block text-[13px] font-[500] text-slate-600">Email address</label>
                    <input id="emailInput" type="email" class="auth-input" placeholder="name@example.com" autocomplete="email" required>
                </div>

                <div>
                    <label for="passwordInput" class="mb-1.5 block text-[13px] font-[500] text-slate-600">
                        Password <span class="text-slate-400">(min. 8 characters)</span>
                    </label>
                    <div class="relative">
                        <input id="passwordInput" type="password" class="auth-input pr-11" placeholder="Create a password" autocomplete="new-password" required>
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
                </div>

                <div>
                    <label for="passwordConfirmInput" class="mb-1.5 block text-[13px] font-[500] text-slate-600">Confirm password</label>
                    <input id="passwordConfirmInput" type="password" class="auth-input" placeholder="Repeat your password" autocomplete="new-password" required>
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
</style>
@endpush

@push('scripts')
<script src="/js/api-client.js"></script>
<script src="/js/register.js"></script>
@endpush
