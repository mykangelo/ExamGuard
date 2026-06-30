@extends('layouts.auth')
@section('title', 'Forgot Password | ExamGuard')

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
                    <h2 class="text-[1.75rem] font-[700] text-slate-900">Forgot password?</h2>
                    <p class="mt-1 text-[14px] text-slate-500">Enter your email and we'll send reset instructions.</p>
                </div>

                <form id="forgotForm" class="space-y-4" novalidate>
                    <input type="text" name="website" id="hp_forgot" value="" autocomplete="off"
                           tabindex="-1" aria-hidden="true"
                           style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;opacity:0;pointer-events:none;">

                    <div>
                        <label for="emailInput" class="mb-1.5 block text-[13px] font-[500] text-slate-600">Email address</label>
                        <input id="emailInput" type="email" class="auth-input" placeholder="name@example.com" autocomplete="email">
                        <p id="emailMsg" class="field-msg err hidden"><i class="ti ti-alert-circle text-[12px]"></i><span></span></p>
                    </div>

                    <button id="submitBtn" type="submit"
                            class="mt-1 flex w-full items-center justify-center gap-2 rounded-full bg-[#3b82f6] px-6 py-3.5 text-[15px] font-[600] text-white transition hover:brightness-110 hover:scale-[1.01] disabled:opacity-60 disabled:cursor-not-allowed">
                        <span id="submitBtnText">Send reset link</span>
                        <svg id="submitSpinner" class="hidden h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                    </button>
                </form>

                <p class="mt-6 text-center text-[13px] text-slate-400">
                    Remember your password?
                    <a href="/login" class="ml-1 font-[600] text-[#3b82f6] transition hover:text-blue-700">Back to sign in</a>
                </p>
            </div>

            <div id="successPanel" class="hidden text-center py-4">
                <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50">
                    <i class="ti ti-mail text-emerald-500 text-[32px]"></i>
                </div>
                <h2 class="mb-2 text-[1.6rem] font-[700] text-slate-900">Check your inbox</h2>
                <p id="successMessage" class="mb-4 text-[14px] text-slate-500 leading-[1.65]"></p>
                <p class="text-[13px] text-slate-400">Didn't receive it? Check spam or try again in a minute.</p>
                <a href="/login"
                   class="mt-6 inline-flex items-center gap-1 text-[13px] font-[600] text-[#3b82f6] transition hover:text-blue-700">
                    <i class="ti ti-arrow-left text-[13px]"></i> Back to sign in
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
<script src="/js/forgot-password.js?v=1"></script>
@endpush
