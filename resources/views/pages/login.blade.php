@extends('layouts.app')

@section('title', 'Login | ExamGuard')

@section('content')
<main class="flex min-h-screen items-center justify-center px-6 py-12">
    <section class="eg-panel w-full max-w-md space-y-6">
        <a href="/" class="eg-brand mx-auto flex w-fit items-center gap-3 text-lg font-bold"><span>EG</span> ExamGuard</a>
        <div class="text-center">
            <h1 class="text-3xl font-bold">Sign in to continue</h1>
            <p class="mt-2 text-slate-300">Select your role to open the correct workspace.</p>
        </div>

        <form id="loginForm" class="space-y-4">
            <div>
                <label for="emailInput" class="mb-2 block text-sm text-slate-300">Email Address</label>
                <input id="emailInput" type="email" class="eg-input" placeholder="name@example.com" required>
            </div>
            <div>
                <label for="passwordInput" class="mb-2 block text-sm text-slate-300">Password</label>
                <input id="passwordInput" type="password" class="eg-input" placeholder="Enter your password" required>
            </div>
            <div>
                <label for="roleSelect" class="mb-2 block text-sm text-slate-300">Role</label>
                <select id="roleSelect" class="eg-input">
                    <option value="professor">Professor</option>
                    <option value="student">Student</option>
                </select>
            </div>
            <button class="eg-btn-primary w-full" type="submit" id="loginBtn">Continue</button>
        </form>
    </section>
</main>
@endsection

@push('scripts')
<script src="/js/api-client.js"></script>
<script src="/js/login.js"></script>
@endpush
