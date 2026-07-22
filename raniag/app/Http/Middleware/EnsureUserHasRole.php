<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403, 'Unauthorized.');
        }

        $allowedRoles = collect($roles)
            ->flatMap(fn (string $role) => explode(',', $role))
            ->map(fn (string $role) => UserRole::tryFrom(trim($role)))
            ->filter()
            ->values();

        if ($allowedRoles->isEmpty() || ! $allowedRoles->contains($user->role)) {
            abort(403, 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}
