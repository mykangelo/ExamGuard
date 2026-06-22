@extends('layouts.app')

@section('title', 'Create Exam - ExamGuard')

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
    @include('partials.professor-sidebar', ['active' => 'create-exam'])

    <section class="space-y-8">
        <div>
            <div class="text-sm font-semibold uppercase tracking-[0.2em] text-sky-300">Professor Interface</div>
            <h1 class="mt-2 text-4xl font-bold">Create a new exam.</h1>
            <p class="mt-3 text-slate-300">Enter exam details, add questions, and save when ready.</p>
        </div>

        <section class="eg-panel space-y-4">
            <h2 class="text-xl font-semibold">Exam Details</h2>
            <input id="examTitleInput" class="eg-input" placeholder="Midterm Examination">
            <textarea id="instructionsInput" class="eg-input min-h-28" placeholder="Enter instructions for students"></textarea>
            <div class="grid gap-4 md:grid-cols-3">
                <input id="timeLimitInput" class="eg-input" type="number" min="1" placeholder="Time limit (minutes)">
                <select id="warningLimitInput" class="eg-input">
                    <option value="3">3 warnings</option>
                    <option value="5">5 warnings</option>
                </select>
                <select id="examClassInput" class="eg-input"><option value="">Save without assigning</option></select>
            </div>
        </section>

        <section class="eg-panel space-y-4">
            <h2 class="text-xl font-semibold">Add Question</h2>
            <textarea id="questionInput" class="eg-input min-h-24" placeholder="Enter your question"></textarea>
            <div class="grid gap-4 md:grid-cols-2">
                <input id="choiceA" class="eg-input" placeholder="Choice A">
                <input id="choiceB" class="eg-input" placeholder="Choice B">
                <input id="choiceC" class="eg-input" placeholder="Choice C">
                <input id="choiceD" class="eg-input" placeholder="Choice D">
            </div>
            <select id="correctAnswerInput" class="eg-input">
                <option value="">Select correct answer</option>
                <option value="0">Choice A</option>
                <option value="1">Choice B</option>
                <option value="2">Choice C</option>
                <option value="3">Choice D</option>
            </select>
            <textarea id="explanationInput" class="eg-input min-h-24" placeholder="Explain why the answer is correct"></textarea>
            <div class="flex flex-wrap gap-3">
                <button id="addQuestionBtn" class="eg-btn-primary" type="button">Add Question</button>
                <button id="clearExamBtn" class="eg-btn-secondary" type="button">Clear Current Form</button>
            </div>
        </section>

        <section class="eg-panel">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xl font-semibold">Current Exam Preview</h2>
                <span id="questionCount" class="eg-badge-warning">No questions added yet.</span>
            </div>
            <div id="questionPreview" class="space-y-4"></div>
            <button id="saveExamBtn" class="eg-btn-primary mt-6" type="button">Save Exam</button>
        </section>

        <section class="eg-panel">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xl font-semibold">Saved Exams</h2>
                <button id="deleteAllExamsBtn" class="eg-btn-secondary" type="button">Delete All Exams</button>
            </div>
            <div id="savedExams" class="space-y-4"></div>
        </section>
    </section>
</main>
@endsection

@push('scripts')
<script src="/js/api-client.js"></script>
<script src="/js/auth-guard.js"></script>
<script src="/js/create-exam.js"></script>
@endpush
