<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Register a new user and start an authenticated session.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Start session, regenerate ID to prevent fixation attacks
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Registration successful.',
            'user'    => $user->only('id', 'name', 'email'),
        ], 201);
    }

    /**
     * Authenticate user and start a secure session.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($request->only('email', 'password'), false)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Regenerate session ID immediately — prevents session fixation
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login successful.',
            'user'    => Auth::user()->only('id', 'name', 'email'),
        ]);
    }

    /**
     * Destroy the authenticated session (logout).
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::logout();

        $request->session()->invalidate();       // Destroy server-side session data
        $request->session()->regenerateToken();  // Regenerate CSRF token

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Return the currently authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()->only('id', 'name', 'email')]);
    }
}
