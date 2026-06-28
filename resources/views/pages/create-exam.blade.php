@extends('layouts.app')

@section('title', 'Create Exam - ExamGuard')

@section('body_attrs')
data-role="professor"
@endsection

@section('content')
@include('partials.header', ['links' => [
    ['href' => '/professor', 'label' => 'Dashboard'],
    ['href' => '/create-exam', 'label' => 'Create Exam'],
    ['href' => '/login', 'label' => 'Logout', 'logout' => true],
]])

<main id="createExamPage" class="mx-auto grid max-w-7xl gap-8 px-6 py-8 lg:grid-cols-[240px_1fr]">
    @include('partials.professor-sidebar', ['active' => 'create-exam'])

    <section>
        @include('partials.create-exam-form', ['embedded' => false])
    </section>
</main>
@endsection

@push('scripts')
<script src="/js/api-client.js"></script>
<script src="/js/auth-guard.js"></script>
<script src="/js/create-exam.js"></script>
@endpush
