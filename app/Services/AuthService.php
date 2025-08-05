<?php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthService
{
    public function login(array $credentials)
    {
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return [
                    'success' => false,
                    'message' => 'Email atau password tidak valid'
                ];
            }

            $user = auth()->user();

            if (!$user->is_active) {
                JWTAuth::invalidate($token);
                return [
                    'success' => false,
                    'message' => 'Akun Anda telah dinonaktifkan'
                ];
            }

            Log::info('User login successful', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role
            ]);

            return [
                'success' => true,
                'data' => [
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => JWTAuth::factory()->getTTL() * 60,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role
                    ]
                ]
            ];
        } catch (JWTException $e) {
            Log::error('JWT Exception during login', [
                'error' => $e->getMessage(),
                'credentials' => $credentials['email'] ?? 'unknown'
            ]);

            return [
                'success' => false,
                'message' => 'Tidak dapat membuat token'
            ];
        }
    }

    public function logout()
    {
        try {
            $user = auth()->user();
            JWTAuth::invalidate(JWTAuth::getToken());

            Log::info('User logout successful', [
                'user_id' => $user->id ?? null,
                'email' => $user->email ?? null
            ]);

            return [
                'success' => true,
                'message' => 'Berhasil logout'
            ];
        } catch (JWTException $e) {
            return [
                'success' => false,
                'message' => 'Gagal logout'
            ];
        }
    }

    public function refresh()
    {
        try {
            $newToken = JWTAuth::refresh();

            return [
                'success' => true,
                'data' => [
                    'access_token' => $newToken,
                    'token_type' => 'bearer',
                    'expires_in' => JWTAuth::factory()->getTTL() * 60
                ]
            ];
        } catch (JWTException $e) {
            return [
                'success' => false,
                'message' => 'Token tidak dapat direfresh'
            ];
        }
    }

    public function me()
    {
        $user = auth()->user();

        return [
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active
            ]
        ];
    }
}
