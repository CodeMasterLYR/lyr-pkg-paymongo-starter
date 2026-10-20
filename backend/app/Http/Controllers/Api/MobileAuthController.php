<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Mobile Auth Controller
 *
 * Handles authentication for mobile clients (Android KMP/Ktor, iOS).
 * Uses Sanctum Personal Access Tokens (Bearer tokens) instead of sessions.
 * All routes in this controller require the X-App-Key header.
 *
 * Ktor (KMP) usage example:
 *   httpClient.post("http://api.example.com/api/mobile/auth/login") {
 *       header("X-App-Key", BuildConfig.APP_KEY)
 *       setBody(LoginRequest(email, password))
 *   }
 */
class MobileAuthController extends Controller
{
    /**
     * Register a new user and return a Sanctum Bearer token.
     * Mobile clients store this token securely (e.g., Android EncryptedSharedPreferences).
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:8|confirmed',
            'device_name' => 'required|string|max:255',  // e.g. "Jerald's Pixel 9"
        ]);

        $user  = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken(
            $validated['device_name'],
            ['*'],                    // abilities: full access
        )->plainTextToken;

        return response()->json([
            'message'      => 'Registration successful.',
            'token'        => $token,
            'token_type'   => 'Bearer',
            'user'         => $user->only('id', 'name', 'email'),
        ], 201);
    }

    /**
     * Authenticate and return a Sanctum Bearer token.
     * One token is issued per device_name — existing tokens for same device are revoked.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'       => 'required|email',
            'password'    => 'required|string',
            'device_name' => 'required|string|max:255',
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();

        // Revoke existing tokens for this device (prevents token accumulation)
        $user->tokens()->where('name', $request->device_name)->delete();

        $token = $user->createToken($request->device_name, ['*'])->plainTextToken;

        return response()->json([
            'message'    => 'Login successful.',
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => $user->only('id', 'name', 'email'),
        ]);
    }

    /**
     * Revoke the current device token (logout from this device only).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out from this device.']);
    }

    /**
     * Revoke all tokens for this user (logout from all devices).
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out from all devices.']);
    }

    /**
     * Return the authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user'   => $request->user()->only('id', 'name', 'email'),
            'device' => $request->user()->currentAccessToken()->name,
        ]);
    }
}
