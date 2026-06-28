@extends('layouts.auth')
@section('title', 'Activate Your Account | ExamGuard')

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3/dist/tabler-icons.min.css">
@endpush

@section('content')
<div class="flex min-h-screen items-center justify-center px-6 py-16"
     style="background: linear-gradient(135deg, #f0f6ff 0%, #f8fafc 60%, #eef2ff 100%);">

    <div class="w-full max-w-md">

        {{-- Logo --}}
        <div class="mb-8 flex items-center justify-center gap-2.5">
            <img src="/images/logo.png" alt="examguard" class="h-11 w-auto object-contain drop-shadow-[0_0_8px_rgba(59,130,246,0.35)]">
            <span class="text-slate-800" style="font-family:'Space Grotesk',sans-serif; font-size:20px; font-weight:600; letter-spacing:-0.4px;">examguard.</span>
        </div>

        {{-- Card --}}
        <div class="rounded-2xl border border-slate-200/80 bg-white px-8 py-10 shadow-[0_4px_32px_rgba(0,0,0,0.07)] text-center">

            {{-- Icon --}}
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-[#3b82f6]/10">
                <i class="ti ti-mail-opened text-[#3b82f6] text-[32px]"></i>
            </div>

            <h1 class="mb-2 text-[1.6rem] font-[700] text-slate-900">Activate your account</h1>

            {{-- Expired banner --}}
            <div id="expiredBanner" class="hidden mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-left">
                <div class="flex items-start gap-2.5">
                    <i class="ti ti-clock-exclamation text-amber-500 text-[16px] shrink-0 mt-0.5"></i>
                    <p class="text-[13px] text-amber-700">Your verification link has expired. Click <strong>Resend email</strong> below to get a new one.</p>
                </div>
            </div>

            {{-- Resent banner --}}
            <div id="resentBanner" class="hidden mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                <div class="flex items-center gap-2.5">
                    <i class="ti ti-circle-check text-emerald-500 text-[16px] shrink-0"></i>
                    <p class="text-[13px] text-emerald-700">Verification email resent! Check your inbox.</p>
                </div>
            </div>

            <p class="mb-1 text-[15px] text-slate-600 leading-[1.6]">
                We've sent an email to
            </p>
            <p id="emailDisplay" class="mb-4 text-[15px] font-[600] text-slate-900 break-all"></p>
            <p class="text-[14px] text-slate-500 leading-[1.65]">
                Check your inbox and click the activation link to get started. The link expires in <strong>60 minutes</strong>.
            </p>

            <div class="my-6 border-t border-slate-100"></div>

            <p class="text-[13px] text-slate-400">
                Can't find the email? Check your spam folder or
                <button id="resendBtn" type="button"
                        class="font-[600] text-[#3b82f6] underline underline-offset-2 transition hover:text-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    resend
                </button>.
            </p>

            <p class="mt-6 text-[13px] text-slate-400">
                Already activated?
                <a href="/login" class="ml-1 font-[600] text-[#3b82f6] transition hover:text-blue-700">Sign in</a>
            </p>
        </div>

        <p class="mt-6 text-center text-[12px] text-slate-400">
            Wrong email?
            <a href="/register" class="font-[500] text-slate-500 hover:text-slate-700 transition">Register again</a>
        </p>

    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var params  = new URLSearchParams(window.location.search);
    var email   = params.get('email') || '';
    var expired = params.get('expired') === '1';

    /* Display email */
    var emailEl = document.getElementById('emailDisplay');
    if (email) emailEl.textContent = decodeURIComponent(email);
    else emailEl.textContent = 'your email address';

    /* Show expired warning */
    if (expired) {
        document.getElementById('expiredBanner').classList.remove('hidden');
    }

    /* Resend */
    var resendBtn   = document.getElementById('resendBtn');
    var resentBanner = document.getElementById('resentBanner');
    var cooldown    = 0;

    function startCooldown(seconds) {
        resendBtn.disabled = true;
        cooldown = seconds;
        var iv = setInterval(function () {
            cooldown--;
            resendBtn.textContent = 'resend (' + cooldown + 's)';
            if (cooldown <= 0) {
                clearInterval(iv);
                resendBtn.disabled = false;
                resendBtn.textContent = 'resend';
            }
        }, 1000);
    }

    resendBtn.addEventListener('click', async function () {
        if (!email) return;
        resendBtn.disabled = true;
        resendBtn.textContent = 'sending…';
        resentBanner.classList.add('hidden');

        try {
            await fetch('/api/email/resend', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ email: decodeURIComponent(email) }),
            });
            resentBanner.classList.remove('hidden');
            document.getElementById('expiredBanner').classList.add('hidden');
        } catch (e) {}

        startCooldown(60);
    });
})();
</script>
@endpush
