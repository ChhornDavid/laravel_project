<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Events\UserStatusUpdated;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Carbon\Carbon;

class AuthController extends Controller
{
    // Create refresh token cookie
    private function createRefreshCookie($refreshToken)
    {
        return cookie(
            'refresh_token',
            $refreshToken,
            60 * 24 * 7, // 7 days
            '/',
            null,
            app()->environment('production'), // Secure only in production
            true,  // HttpOnly
            false,
            'Lax'
        );
    }

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

        return response()->json([
            'message' => 'User registered successfully!',
            'user' => $user,
            'access_token' => $accessToken,
            'expires_in' => auth()->factory()->getTTL() * 60
        ])->withCookie($this->createRefreshCookie($refreshToken));
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

        if ($user instanceof User) {
            $user->update(['is_online' => true]);
            event(new UserStatusUpdated($user));
        }

        $refreshToken = JWTAuth::customClaims([
            'token_type' => 'refresh',
            'exp' => Carbon::now()->addDays(7)->timestamp
        ])->fromUser($user);

        return response()->json([
            'message' => 'Login successful!',
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->type,
            'access_token' => $accessToken,
            'expires_in' => auth()->factory()->getTTL() * 60
        ])->withCookie($this->createRefreshCookie($refreshToken));
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
            $clearCookie = cookie('refresh_token', '', -1, '/');
            return response()->json(['message' => 'Logged out successfully!'])
                ->withCookie($clearCookie);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Failed to logout!'], 500);
        }
    }

    public function refresh(Request $request)
    {
        try {
            $refreshToken = $request->cookie('refresh_token') ?? $request->input('refresh_token');

            if (!$refreshToken) {
                return response()->json(['error' => 'Refresh token not found'], 401);
            }

            $token = new \Tymon\JWTAuth\Token($refreshToken);
            $payload = JWTAuth::manager()->decode($token);

            if (!isset($payload['token_type']) || $payload['token_type'] !== 'refresh') {
                return response()->json(['error' => 'Invalid token type'], 401);
            }

            if (Carbon::now()->timestamp > $payload['exp']) {
                return response()->json(['error' => 'Refresh token expired'], 401);
            }

            $user = JWTAuth::setToken($token)->toUser();

            if (!$user) {
                return response()->json(['error' => 'User not found'], 401);
            }

            $newAccessToken = JWTAuth::fromUser($user);
            $newRefreshToken = JWTAuth::customClaims([
                'token_type' => 'refresh',
                'exp' => Carbon::now()->addDays(7)->timestamp
            ])->fromUser($user);

            return response()->json([
                'access_token' => $newAccessToken,
                'expires_in' => auth()->factory()->getTTL() * 60
            ])->withCookie($this->createRefreshCookie($newRefreshToken));

        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Refresh token expired'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'Invalid refresh token'], 401);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token error'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not refresh token'], 500);
        }
    }
}