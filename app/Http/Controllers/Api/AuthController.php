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
            'refresh_token',   // Cookie name
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
            'refresh_token',   // Cookie name
            $token,         // Token value
            60 * 24 * 7,             // Expiry time (in minutes)
            '/',            // Path
            null,           // Domain (null for default)
            true,           // Secure (true for HTTPS)
            true,
            false,
            'None'
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

    public function refresh(Request $request)
    {
        try {
            // Get the token from the cookie
            $token = $request->cookie('refresh_token');

            if (!$token) {
                return response()->json(['message' => 'No refresh token found'], 401);
            }

            // Set the token manually so JWTAuth can refresh it
            JWTAuth::setToken($token);
            $newToken = JWTAuth::refresh();

            return response()->json([
                'token' => $newToken,
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Invalid or expired refresh token'], 401);
        }
    }
}
