@extends('layouts.app')

@section('title', 'Classes - ExamGuard')

@section('body_attrs')
data-role="professor"
@endsection

@section('content')
@include('partials.header', ['links' => [
    ['href' => '/professor', 'label' => 'Dashboard'],
    ['href' => '/create-exam', 'label' => 'Create Exam'],
    ['href' => '/professor-classes', 'label' => 'Classes'],
    ['href' => '/login', 'label' => 'Logout', 'logout' => true],
]])

<main class="mx-auto grid max-w-7xl gap-8 px-6 py-8 lg:grid-cols-[240px_1fr]">
    @include('partials.professor-sidebar', ['active' => 'classes'])

    <section class="space-y-8">
        <div>
            <div class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-300">Professor Interface</div>
            <h1 class="mt-2 text-4xl font-bold">Manage classes.</h1>
            <p class="mt-3 text-slate-300">Create joinable classes, enroll students with a class code, and assign saved exams.</p>
        </div>

        <section class="eg-panel space-y-4">
            <h2 class="text-xl font-semibold">Create Class</h2>
            <form id="createClassForm" class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <input id="classNameInput" class="eg-input" placeholder="BSIT 3A">
                    <input id="classSubjectInput" class="eg-input" placeholder="Computer Networks">
                </div>
                <button class="eg-btn-primary" type="submit">Create Class</button>
            </form>
        </section>

        <section class="eg-panel space-y-4">
            <h2 class="text-xl font-semibold">Assign Saved Exam</h2>
            <form id="assignExamForm" class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <select id="examSelect" class="eg-input"></select>
                    <select id="classSelect" class="eg-input"></select>
                </div>
                <button class="eg-btn-primary" type="submit">Assign Exam</button>
            </form>
        </section>

        <section class="eg-panel">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xl font-semibold">Your Classes</h2>
                <span class="eg-badge-success" id="classCount">0 classes</span>
            </div>
            <div id="classList" class="space-y-4"></div>
        </section>
    </section>
</main>
@endsection

@push('scripts')
<script src="/js/api-client.js"></script>
<script src="/js/auth-guard.js"></script>
<script src="/js/professor-classes.js"></script>
@endpush
