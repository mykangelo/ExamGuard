<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
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

        try {
            $user = User::create([
                'name'     => $input['name'],
                'email'    => $input['email'],
                'password' => $input['password'],
                'role'     => $input['role'],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'error' => 'Unable to create account. If you already registered, try signing in or use a different email.',
            ], 500);
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'needs_verification' => true,
                'email'              => $user->email,
                'email_sent'         => false,
                'error'              => 'Account created, but the verification email could not be sent. Use Resend or try again shortly.',
            ], 201);
        }

        return response()->json([
            'needs_verification' => true,
            'email'              => $user->email,
            'email_sent'         => true,
        ], 201);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        if ($request->filled('website')) {
            return response()->json(['error' => 'Invalid request.'], 400);
        }

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'ok' => true,
            'message' => 'If an account exists for that email, we sent password reset instructions.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                RateLimiter::clear('login.'.Str::lower($user->email).'.'.request()->ip());
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'ok' => true,
                'message' => 'Password updated. You can sign in now.',
            ]);
        }

        return response()->json([
            'error' => match ($status) {
                Password::INVALID_TOKEN => 'This reset link is invalid or has expired.',
                Password::INVALID_USER => 'We could not find an account with that email.',
                default => 'Unable to reset password. Please try again.',
            },
        ], 422);
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
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'department' => ['nullable', 'string', 'max:120'],
            'yearLevel'  => ['nullable', 'string', 'max:40'],
            'studentId'  => ['nullable', 'string', 'max:40'],
        ]);

        $emailChanged = strcasecmp($user->email, $input['email']) !== 0;
        $user->name = $input['name'];
        $user->email = $input['email'];

        $prefs = $user->preferencesWithDefaults();
        $prefs['department'] = trim($input['department'] ?? '') ?: '';
        $prefs['yearLevel'] = trim($input['yearLevel'] ?? '') ?: '';
        $prefs['studentId'] = trim($input['studentId'] ?? '') ?: '';
        $user->preferences = $prefs;

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
            'emailExamAssigned'   => ['sometimes', 'boolean'],
            'emailClassUpdates'   => ['sometimes', 'boolean'],
            'emailExamReminder'   => ['sometimes', 'boolean'],
            'emailExamResults'    => ['sometimes', 'boolean'],
            'defaultWarningLimit' => ['sometimes', 'integer', 'in:3,5'],
            'defaultTimeLimit'    => ['sometimes', 'nullable', 'integer', 'min:1', 'max:480'],
        ]);

        $user = $request->user();
        $user->preferences = array_merge($user->preferencesWithDefaults(), $input);
        $user->save();

        return response()->json(['user' => $user->toAuthArray()]);
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $input = $request->validate([
            'avatar' => ['required', 'image', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $input['avatar']->store('avatars/'.$user->id, 'public');
        $user->avatar_path = $path;
        $user->save();

        return response()->json(['user' => $user->toAuthArray()]);
    }

    public function logoutAllSessions(Request $request): JsonResponse
    {
        $user = $request->user();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }

    public function destroyAccount(Request $request): JsonResponse
    {
        $input = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($input['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Password is incorrect.'],
            ]);
        }

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user->delete();

        return response()->json(['ok' => true]);
    }
}
