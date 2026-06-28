@extends('layouts.app')

@section('title', 'Exam Room')

@section('body_attrs')
data-role="student"
@endsection

@section('content')
<header class="border-b border-white/10 bg-slate-950/50 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
        <a href="/student" class="eg-brand flex items-center gap-2.5" style="font-family:'Space Grotesk',sans-serif; font-size:18px; font-weight:600; letter-spacing:-0.4px;"><img src="/images/logo.png" alt="examguard logo" class="h-9 w-auto object-contain"> examguard.</a>
        <div class="flex items-center gap-3">
            <span class="eg-badge-success" id="tabStatus">Tab Active</span>
            <span class="eg-badge-warning"><span id="warningCount">0</span> / 3 Warnings</span>
        </div>
    </div>
</header>

<div class="warning-toast" id="warningToast">Monitoring warning recorded.</div>

<main class="mx-auto grid max-w-7xl gap-6 px-6 py-6 lg:grid-cols-[340px_1fr]">
    <section class="eg-panel space-y-4">
        <h2 class="text-xl font-semibold">Monitoring Panel</h2>
        <div class="relative overflow-hidden rounded-2xl bg-black/40">
            <video id="cameraVideo" class="hidden w-full" autoplay muted playsinline></video>
            <div id="cameraPlaceholder" class="p-6 text-center text-sm text-slate-300">
                <strong class="block text-base text-white">Camera Preview</strong>
                <p class="mt-2">Enable your camera before starting.</p>
            </div>
        </div>
        <button class="eg-btn-primary w-full" id="enableCameraBtn">Enable Camera</button>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between rounded-xl bg-white/5 px-4 py-2"><span>Camera</span><strong id="cameraStatus">Waiting</strong></div>
            <div class="flex justify-between rounded-xl bg-white/5 px-4 py-2"><span>Session</span><strong id="sessionStatus">In Progress</strong></div>
        </div>
        <button class="eg-btn-secondary w-full" id="simulateBtn">Simulate Violation</button>
        <div id="logList" class="space-y-2 text-sm"><div class="rounded-xl bg-white/5 px-4 py-2">No violations recorded.</div></div>
    </section>

    <section class="eg-panel">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-300">Practice Room</div>
                <h1 class="text-3xl font-bold">Assessment Window</h1>
            </div>
            <button class="eg-btn-primary" id="submitBtn">Submit Monitoring Session</button>
        </div>
        <div class="rounded-2xl border border-dashed border-white/10 bg-white/5 p-8 text-slate-300">
            <h2 class="mb-3 text-xl font-semibold text-white">Monitoring demo</h2>
            <p>This room demonstrates camera and tab monitoring. Assigned class exams are taken from the student dashboard.</p>
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script src="/js/api-client.js"></script>
<script src="/js/auth-guard.js"></script>
<script src="/js/monitoring.js"></script>
@endpush
