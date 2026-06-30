@extends('layouts.auth')
@section('title', 'Reset Password | ExamGuard')

@section('content')
<div class="auth-mobile-shell flex min-h-screen min-h-[100dvh]">

    @include('partials.auth-panel')

    <div class="flex w-full min-w-0 flex-1 flex-col items-center justify-center px-6 py-12"
         style="background: linear-gradient(to right,
                    #0a1628 0%,
                    #1d3a60 4%,
                    #4a7299 9%,
                    #9ec4d8 15%,
                    #d4eaf5 21%,
                    #edf5fb 26%,
                    #f8fafc 32%);">

        @include('partials.auth-mobile-header')

        <div class="w-full max-w-sm">

            <div id="formPanel">
                <div class="mb-8">
                    <h2 class="text-[1.75rem] font-[700] text-slate-900">Set a new password</h2>
                    <p class="mt-1 text-[14px] text-slate-500">Choose a strong password for your account.</p>
                </div>

                <form id="resetForm" class="space-y-4" novalidate>
                    <input type="hidden" id="tokenInput" value="{{ request('token') }}">

                    <div>
                        <label for="emailInput" class="mb-1.5 block text-[13px] font-[500] text-slate-600">Email address</label>
                        <input id="emailInput" type="email" class="auth-input" value="{{ request('email') }}" autocomplete="email" readonly>
                    </div>

                    <div>
                        <label for="passwordInput" class="mb-1.5 block text-[13px] font-[500] text-slate-600">New password</label>
                        <div class="relative">
                            <input id="passwordInput" type="password" class="auth-input pr-11" placeholder="At least 8 characters" autocomplete="new-password">
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

                    <div>
                        <label for="passwordConfirmInput" class="mb-1.5 block text-[13px] font-[500] text-slate-600">Confirm password</label>
                        <input id="passwordConfirmInput" type="password" class="auth-input" placeholder="Repeat your password" autocomplete="new-password">
                        <p id="passwordConfirmMsg" class="field-msg err hidden"><i class="ti ti-alert-circle text-[12px]"></i><span></span></p>
                    </div>

                    <button id="submitBtn" type="submit"
                            class="mt-1 flex w-full items-center justify-center gap-2 rounded-full bg-[#3b82f6] px-6 py-3.5 text-[15px] font-[600] text-white transition hover:brightness-110 hover:scale-[1.01] disabled:opacity-60 disabled:cursor-not-allowed">
                        <span id="submitBtnText">Update password</span>
                        <svg id="submitSpinner" class="hidden h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                    </button>
                </form>

                <p class="mt-6 text-center text-[13px] text-slate-400">
                    <a href="/login" class="font-[600] text-[#3b82f6] transition hover:text-blue-700">Back to sign in</a>
                </p>
            </div>

            <div id="successPanel" class="hidden text-center py-4">
                <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50">
                    <i class="ti ti-circle-check text-emerald-500 text-[32px]"></i>
                </div>
                <h2 class="mb-2 text-[1.6rem] font-[700] text-slate-900">Password updated</h2>
                <p class="mb-6 text-[14px] text-slate-500 leading-[1.65]">Your password has been changed. You can sign in with your new credentials.</p>
                <a href="/login"
                   class="inline-flex items-center justify-center rounded-full bg-[#3b82f6] px-6 py-3 text-[15px] font-[600] text-white transition hover:brightness-110">
                    Sign in
                </a>
            </div>

            <div id="invalidPanel" class="hidden text-center py-4">
                <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-red-50">
                    <i class="ti ti-link-off text-red-500 text-[32px]"></i>
                </div>
                <h2 class="mb-2 text-[1.6rem] font-[700] text-slate-900">Invalid reset link</h2>
                <p class="mb-6 text-[14px] text-slate-500 leading-[1.65]">This link is missing details or has expired. Request a new reset email.</p>
                <a href="/forgot-password"
                   class="inline-flex items-center justify-center rounded-full bg-[#3b82f6] px-6 py-3 text-[15px] font-[600] text-white transition hover:brightness-110">
                    Request new link
                </a>
            </div>

        </div>
    </div>
</div>
@endsection

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3/dist/tabler-icons.min.css">
@endpush

@push('scripts')
<script src="/js/api-client.js?v=4"></script>
<script src="/js/reset-password.js?v=1"></script>
@endpush
