<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Two login modes:
 * - Stateful (Next.js on a SANCTUM_STATEFUL_DOMAINS origin): session cookie, CSRF-protected, no token returned.
 * - Stateless (legacy pages, other API clients): bearer token.
 */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $email = $request->input('email');
        $password = $request->input('password');

        if (! is_string($email) || ! is_string($password) || $email === '' || $password === '') {
            return response()->json(['error' => 'E-posta ve şifre gerekli.'], 400);
        }

        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            Log::channel('security')->warning('Failed login', [
                'ip' => $request->ip(),
                'user_id' => $user?->id,
            ]);

            return response()->json(['error' => 'E-posta veya şifre hatalı.'], 401);
        }

        if (! $user->is_active) {
            Log::channel('security')->warning('Login attempt by inactive user', ['ip' => $request->ip(), 'user_id' => $user->id]);

            return response()->json(['error' => 'Hesabınız devre dışı.'], 403);
        }

        if ($request->hasSession()) {
            Auth::guard('web')->login($user);
            $request->session()->regenerate();

            return response()->json(['user' => $this->userPayload($user)]);
        }

        $token = $user->createToken('api', [$user->role->value])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->is_active) {
            return response()->json(['error' => 'Hesabınız devre dışı.'], 403);
        }

        return response()->json(['user' => $this->userPayload($user)]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['success' => true]);
    }

    /** @return array<string, mixed> */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->value,
        ];
    }
}
