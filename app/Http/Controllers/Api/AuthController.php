<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Events\UserStatusUpdated;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;


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

        // Attempt login and get access token
        if (! $accessToken = JWTAuth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid email or password',
            ], 401);
        }

        $user = Auth::user();

        // Update user status if needed
        if ($user instanceof User) {
            $user->update(['is_online' => true]);
            event(new UserStatusUpdated($user));
        }

        // ✅ Create a refresh token with custom claim
        $refreshToken = JWTAuth::customClaims(['type' => 'refresh'])->fromUser($user);

        // ✅ Set refresh token in HttpOnly cookie
        $cookie = cookie(
            'refresh_token',
            $refreshToken,
            60 * 24 * 7, // 7 days in minutes
            '/',
            null,
            true, // Secure (set to false for local HTTP testing)
            true, // HttpOnly
            false,
            'None' // SameSite
        );

        return response()->json([
            'message' => 'Login successful!',
            'id' => $user->id,
            'name' => $user->name,
            'token' => $accessToken,
            'role' => $user->type,
        ])->withCookie($cookie);
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
            $newToken = JWTAuth::parseToken()->refresh();

            return response()->json([
                'token' => $newToken
            ]);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        }
    }
}
