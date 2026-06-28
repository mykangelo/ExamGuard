@extends('layouts.app')

@section('title', 'Class Exam - ExamGuard')

@section('body_attrs')
data-role="student"
@endsection

@section('content')
<header class="border-b border-white/10 bg-slate-950/50 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
        <a href="/student" class="eg-brand flex items-center gap-2.5 text-lg font-bold"><img src="/images/logo.png" alt="ExamGuard logo"> ExamGuard</a>
        <div class="flex items-center gap-3">
            <span class="eg-badge-success" id="tabStatus">Tab Active</span>
            <span class="eg-badge-warning"><span id="warningCount">0</span> / <span id="warningLimitDisplay">3</span> Warnings</span>
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
            <div class="flex justify-between rounded-xl bg-white/5 px-4 py-2"><span>Time Remaining</span><strong id="examTimer">--:--</strong></div>
        </div>
        <button class="eg-btn-secondary w-full" id="simulateBtn">Simulate Violation</button>
        <div id="logList" class="space-y-2 text-sm"><div class="rounded-xl bg-white/5 px-4 py-2">No violations recorded.</div></div>
    </section>

    <section class="eg-panel">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-300">Class Exam</div>
                <h1 class="text-3xl font-bold" id="activeExamTitle">Loading exam...</h1>
            </div>
            <button class="eg-btn-primary" id="submitExamBtn">Submit Exam</button>
        </div>
        <p id="activeExamInstructions" class="mb-6 text-slate-300"></p>
        <div id="examQuestions" class="space-y-6"></div>
    </section>
</main>
@endsection

@push('scripts')
<script src="/js/api-client.js"></script>
<script src="/js/auth-guard.js"></script>
<script src="/js/monitoring.js"></script>
<script src="/js/take-exam.js"></script>
@endpush
