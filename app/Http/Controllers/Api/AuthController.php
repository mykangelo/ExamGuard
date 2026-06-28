<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        /* Honeypot — silently reject bots that fill the hidden field */
        if ($request->filled('website')) {
            return response()->json(['error' => 'Invalid request.'], 400);
        }

        $input = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /* ── Per-account brute-force lockout ────────────────────────
         * Key: email (lowercased) + IP  |  5 failures → 15-min lock
         */
        $throttleKey = 'login.' . Str::lower($input['email']) . '.' . $request->ip();
        $maxAttempts = 5;
        $decaySeconds = 900; // 15 minutes

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = (int) ceil($seconds / 60);
            return response()->json([
                'error'       => "Too many failed attempts. Please try again in {$minutes} minute(s).",
                'locked_out'  => true,
                'retry_after' => $seconds,
            ], 429);
        }

        $user = User::where('email', $input['email'])->first();

        if (! $user || ! Hash::check($input['password'], $user->password)) {
            RateLimiter::hit($throttleKey, $decaySeconds);
            return response()->json([
                'error' => 'Invalid email or password.',
            ], 401);
        }

        /* Successful credential match — clear the lockout counter */
        RateLimiter::clear($throttleKey);

        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            return response()->json([
                'error'              => 'Please verify your email before signing in.',
                'needs_verification' => true,
                'email'              => $user->email,
            ], 403);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['user' => $user->toAuthArray()]);
    }

    public function register(Request $request): JsonResponse
    {
        /* Honeypot */
        if ($request->filled('website')) {
            return response()->json(['error' => 'Invalid request.'], 400);
        }

        $input = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'unique:users,email'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'role'                  => ['required', 'in:professor,student'],
        ]);

        $user = User::create([
            'name'     => $input['name'],
            'email'    => $input['email'],
            'password' => $input['password'],
            'role'     => $input['role'],
        ]);

        $user->sendEmailVerificationNotification();

        return response()->json([
            'needs_verification' => true,
            'email'              => $user->email,
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()->toAuthArray()]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $input = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        $emailChanged = strcasecmp($user->email, $input['email']) !== 0;
        $user->name = $input['name'];
        $user->email = $input['email'];

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json([
            'user'                    => $user->fresh()->toAuthArray(),
            'email_verification_sent' => $emailChanged,
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $input = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($input['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->password = $input['password'];
        $user->save();

        return response()->json(['ok' => true]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $input = $request->validate([
            'emailExamSubmitted'  => ['sometimes', 'boolean'],
            'emailViolations'     => ['sometimes', 'boolean'],
            'defaultWarningLimit' => ['sometimes', 'integer', 'in:3,5'],
            'defaultTimeLimit'    => ['sometimes', 'nullable', 'integer', 'min:1', 'max:480'],
        ]);

        $user = $request->user();
        $user->preferences = array_merge($user->preferencesWithDefaults(), $input);
        $user->save();

        return response()->json(['user' => $user->toAuthArray()]);
    }
}
