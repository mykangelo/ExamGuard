@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('body_attrs')
data-role="student"
@endsection

@section('content')
@include('partials.header', ['links' => [
    ['href' => '/student', 'label' => 'Dashboard'],
    ['href' => '/student#classes', 'label' => 'My Classes'],
    ['href' => '/exam-room', 'label' => 'Exam Room'],
    ['href' => '/login', 'label' => 'Logout', 'logout' => true],
]])

<main class="mx-auto max-w-6xl space-y-8 px-6 py-10">
    <section class="text-center">
        <div class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-300">Student Interface</div>
        <h1 class="mt-2 text-4xl font-bold">Prepare for your monitored exam.</h1>
        <p class="mx-auto mt-3 max-w-2xl text-slate-300">Before entering the exam room, make sure your camera is working and you understand the monitoring rules.</p>
    </section>

    <section class="grid gap-6 md:grid-cols-3">
        @foreach ([
            ['Camera', 'Required', 'Your camera must be enabled before the exam starts and remain available during the session.'],
            ['Tabs', 'Stay on page', 'Tab switching or losing page focus may create a warning in your session record.'],
            ['Exam', 'Class assessment', 'Assigned class exams appear below and remain protected by ExamGuard monitoring.'],
        ] as [$label, $title, $text])
        <article class="eg-panel">
            <div class="mb-2 text-sm font-semibold text-sky-300">{{ $label }}</div>
            <h3 class="mb-2 text-xl font-semibold">{{ $title }}</h3>
            <p class="text-slate-300">{{ $text }}</p>
        </article>
        @endforeach
    </section>

    <section class="eg-panel" id="classes">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-300">Classroom</div>
                <h2 class="text-2xl font-bold">Join a class</h2>
            </div>
            <span class="eg-badge-success" id="studentIdentity">Not enrolled</span>
        </div>
        <form id="joinClassForm" class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="studentNameInput" class="mb-2 block text-sm text-slate-300">Student Name</label>
                    <input id="studentNameInput" class="eg-input" placeholder="Enter your full name">
                </div>
                <div>
                    <label for="classCodeInput" class="mb-2 block text-sm text-slate-300">Class Code</label>
                    <input id="classCodeInput" class="eg-input" placeholder="Enter 6-character code" maxlength="6">
                </div>
            </div>
            <button class="eg-btn-primary" type="submit">Join Class</button>
        </form>
    </section>

    <section class="eg-panel" id="exam-key">
        <div class="mb-6">
            <div class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-300">Exam access</div>
            <h2 class="text-2xl font-bold">Enter an exam key</h2>
            <p class="mt-2 text-slate-300">Your professor will share an 8-character key when an exam is published.</p>
        </div>
        <form id="joinExamForm" class="flex flex-wrap items-end gap-3">
            <div class="min-w-[200px] flex-1">
                <label for="examKeyInput" class="mb-2 block text-sm text-slate-300">Exam key</label>
                <input id="examKeyInput" class="eg-input font-mono uppercase tracking-widest" placeholder="XXXXXXXX" maxlength="8" autocomplete="off">
            </div>
            <button class="eg-btn-primary" type="submit">Start exam</button>
        </form>
    </section>

    <section class="eg-panel">
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-2xl font-bold">Available Exams</h2>
            <span class="eg-badge-warning" id="availableExamCount">0 exams</span>
        </div>
        <div id="availableExamList" class="space-y-4"></div>
    </section>

    <div class="text-center">
        <a href="/exam-room" class="eg-btn-primary">Enter Exam Room</a>
    </div>
</main>
@endsection

@push('scripts')
<script src="/js/api-client.js?v=7"></script>
<script src="/js/auth-guard.js"></script>
<script src="/js/student.js?v=3"></script>
@endpush
