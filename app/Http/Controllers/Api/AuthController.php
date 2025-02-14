<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Events\UserStatusUpdated;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);


        $token = JWTAuth::fromUser($user);

        $cookie = cookie(
            'auth_token',   // Cookie name
            $token,         // Token value
            60,             // Expiry time (in minutes)
            '/',            // Path
            null,           // Domain (null for default)
            true,           // Secure (true for HTTPS)
            true
        );

        return response()->json([
            'message' => 'User registered successfully!',
            'user' => $user,
            'token' => $token,
        ], 201)->withCookie($cookie);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (! $token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid email or password',
            ], 401);
        }
        $user = Auth::user();
        if ($user instanceof User) {
            $user->update(['is_online' => true]);

            // Broadcast the online status update
            event(new UserStatusUpdated($user));
        }

        $cookie = cookie(
            'auth_token',   // Cookie name
            $token,         // Token value
            60,             // Expiry time (in minutes)
            '/',            // Path
            null,           // Domain (null for default)
            true,           // Secure (true for HTTPS)
            true
        );

        return response()->json([
            'message' => 'Login successful!',
            'id' => $user->id,
            'name' => $user->name,
            'token' => $token,
            'role' => $user->type,
        ], 200)->withCookie($cookie);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user instanceof User) {

            $user->update(['is_online' => false]); // Mark user as offline
            // Broadcast the offline status update
            event(new UserStatusUpdated($user));
        }

        event(new UserStatusUpdated($user));

        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'message' => 'Logged out successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to logout!',
            ], 500);
        }
    }

    public function refresh()
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());

            $cookie = cookie(
                'auth_token',   // Cookie name
                $newToken,      // Token value
                60,             // Expiry time (in minutes)
                '/',            // Path
                null,           // Domain (null for default)
                true,           // Secure (true for HTTPS)
                true            // HttpOnly
            );

            return response()->json([
                'message' => 'Token refreshed successfully!',
                'token' => $newToken,
            ], 200)->withCookie($cookie);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to refresh token!',
                'error' => $e->getMessage(),
            ], 401);
        }
    }
}
