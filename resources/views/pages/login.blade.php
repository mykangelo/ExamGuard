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
            <img src="/images/logo.png" alt="ExamGuard" class="h-8 w-8">
            <span class="text-[18px] font-[700] text-slate-800">ExamGuard</span>
        </div>

        <div class="w-full max-w-sm">

            <div class="mb-8">
                <h2 class="text-[1.75rem] font-[700] text-slate-900">Sign in</h2>
                <p class="mt-1 text-[14px] text-slate-500">Welcome back. Enter your credentials to continue.</p>
            </div>

            {{-- Error message --}}
            <div id="loginError" class="hidden mb-5 flex items-center gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-[13px] text-red-600">
                <i class="ti ti-alert-circle text-[16px] shrink-0"></i>
                <span id="loginErrorText"></span>
            </div>

            <form id="loginForm" class="space-y-4" novalidate>

                <div>
                    <label for="emailInput" class="mb-1.5 block text-[13px] font-[500] text-slate-600">Email address</label>
                    <input id="emailInput" type="email" class="auth-input" placeholder="name@example.com" autocomplete="email" required>
                </div>

                <div>
                    <div class="mb-1.5 flex items-center justify-between">
                        <label for="passwordInput" class="text-[13px] font-[500] text-slate-600">Password</label>
                        <a href="#" class="text-[12px] text-[#3b82f6] transition hover:text-blue-700">Forgot password?</a>
                    </div>
                    <div class="relative">
                        <input id="passwordInput" type="password" class="auth-input pr-11" placeholder="Enter your password" autocomplete="current-password" required>
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
                </div>

                <button id="loginBtn" type="submit"
                        class="mt-1 flex w-full items-center justify-center gap-2.5 rounded-full bg-[#3b82f6] px-6 py-3.5 text-[15px] font-[600] text-white transition hover:brightness-110 hover:scale-[1.01] disabled:opacity-60 disabled:cursor-not-allowed">
                    <span id="loginBtnText">Continue</span>
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

        </div>
    </div>

</div>
@endsection

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3/dist/tabler-icons.min.css">
@endpush

@push('scripts')
<script src="/js/api-client.js"></script>
<script src="/js/login.js"></script>
@endpush
