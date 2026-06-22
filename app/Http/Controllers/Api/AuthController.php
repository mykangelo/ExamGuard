<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $input = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'role' => ['required', 'in:professor,student'],
        ]);

        $user = User::where('email', $input['email'])
            ->where('role', $input['role'])
            ->first();

        if (! $user || ! Hash::check($input['password'], $user->password)) {
            return response()->json(['error' => 'Invalid email, password, or role.'], 401);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['user' => $user->toAuthArray()]);
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
}
