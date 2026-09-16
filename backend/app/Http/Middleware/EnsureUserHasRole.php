<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage: ->middleware('role:admin,manager')
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $allowed = array_map(fn (string $role) => UserRole::from($role), $roles);

        if (! $user || ! $user->is_active || ! $user->hasRole(...$allowed)) {
            return response()->json(['error' => 'Bu işlem için yetkiniz yok.'], 403);
        }

        return $next($request);
    }
}
