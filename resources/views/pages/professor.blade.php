@extends('layouts.app')

@section('title', 'Professor Dashboard')

@section('body_attrs')
data-role="professor"
@endsection

@section('content')
@include('partials.header', ['links' => [
    ['href' => '/professor', 'label' => 'Dashboard'],
    ['href' => '/create-exam', 'label' => 'Create Exam'],
    ['href' => '/professor-classes', 'label' => 'Classes'],
    ['href' => '/student', 'label' => 'Student View'],
    ['href' => '/login', 'label' => 'Logout', 'logout' => true],
]])

<main class="mx-auto grid max-w-7xl gap-8 px-6 py-8 lg:grid-cols-[240px_1fr]">
    @include('partials.professor-sidebar', ['active' => 'dashboard'])

    <section class="space-y-8">
        <div>
            <div class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-300">Professor Interface</div>
            <h1 class="mt-2 text-4xl font-bold">Create and monitor exam sessions.</h1>
            <p class="mt-3 max-w-3xl text-slate-300">Build class exams, assign them to students, and review participation logs with scores and warning counts.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="eg-panel">
                <h2 class="mb-4 text-xl font-semibold">Active Session Summary</h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between rounded-2xl bg-white/5 px-4 py-3"><span>Enrolled Students</span><strong id="enrolledStudentCount">0</strong></div>
                    <div class="flex items-center justify-between rounded-2xl bg-white/5 px-4 py-3"><span>Warnings Recorded</span><strong id="recordedWarningCount">0</strong></div>
                    <div class="flex items-center justify-between rounded-2xl bg-white/5 px-4 py-3"><span>Exam Submissions</span><strong id="submissionCount">0</strong></div>
                </div>
            </section>

            <section class="eg-panel">
                <h2 class="mb-4 text-xl font-semibold">Quick Actions</h2>
                <div class="flex flex-col gap-3">
                    <a href="/create-exam" class="eg-btn-primary">Create Exam</a>
                    <a href="/professor-classes" class="eg-btn-secondary">Manage Classes</a>
                </div>
            </section>
        </div>

        <section class="eg-panel">
            <h2 class="mb-4 text-xl font-semibold">Participation Logs</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="border-b border-white/10 text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Student</th>
                            <th class="px-4 py-3">Exam</th>
                            <th class="px-4 py-3">Time</th>
                            <th class="px-4 py-3">Result</th>
                        </tr>
                    </thead>
                    <tbody id="participationLogBody"></tbody>
                </table>
            </div>
        </section>
    </section>
</main>
@endsection

@push('scripts')
<script src="/js/api-client.js"></script>
<script src="/js/auth-guard.js"></script>
<script src="/js/professor.js"></script>
@endpush
