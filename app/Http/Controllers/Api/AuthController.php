<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Events\UserStatusUpdated;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;


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

        $accessToken = JWTAuth::fromUser($user);

        $refreshToken = JWTAuth::customClaims([
            'token_type' => 'refresh',
            'exp' => Carbon::now()->addDays(7)->timestamp
        ])->fromUser($user);

        $refreshCookie = cookie(
            'refresh_token',
            $refreshToken,
            60 * 24 * 7, // 7 days
            '/',
            null,
            true,    // Secure
            true,    // HttpOnly
            false,
            'None'   // SameSite
        );

        return response()->json([
            'message' => 'User registered successfully!',
            'user' => $user,
            'access_token' => $accessToken,
            'expires_in' => auth()->factory()->getTTL() * 60
        ], 201)->withCookie($refreshCookie);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!$accessToken = JWTAuth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        $user = Auth::user();

        // Update online status
        if ($user instanceof User) {
            $user->update(['is_online' => true]);
            event(new UserStatusUpdated($user));
        }

        $refreshToken = JWTAuth::customClaims([
            'token_type' => 'refresh',
            'exp' => Carbon::now()->addDays(7)->timestamp
        ])->fromUser($user);

        $refreshCookie = cookie(
            'refresh_token',
            $refreshToken,
            60 * 24 * 7,
            '/',
            null,
            true,    // Secure
            true,    // HttpOnly
            false,
            'None'
        );

        return response()->json([
            'message' => 'Login successful!',
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->type,
            'access_token' => $accessToken,
            'expires_in' => auth()->factory()->getTTL() * 60
        ])->withCookie($refreshCookie);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $user->update(['is_online' => false]);
            event(new UserStatusUpdated($user));
        }

        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            // Delete the refresh token cookie
            $clearCookie = cookie('refresh_token', '', -1);

            return response()->json([
                'message' => 'Logged out successfully!'
            ])->withCookie($clearCookie);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to logout!'], 500);
        }
    }

    public function refresh()
    {
        try {
            $refreshToken = request()->cookie('refresh_token');

            if (!$refreshToken) {
                return response()->json(['error' => 'Refresh token not found'], 401);
            }

            $payload = JWTAuth::setToken($refreshToken)->getPayload();

            if ($payload->get('token_type') !== 'refresh') {
                return response()->json(['error' => 'Invalid token type'], 401);
            }

            $user = JWTAuth::setToken($refreshToken)->toUser();

            $newAccessToken = JWTAuth::fromUser($user);

            return response()->json([
                'access_token' => $newAccessToken,
                'expires_in' => auth()->factory()->getTTL() * 60
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid or expired refresh token'], 401);
        }
    }
}
