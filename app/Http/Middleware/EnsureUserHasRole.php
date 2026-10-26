<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Allow the request through only when the user has one of the given roles.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            throw new AuthenticationException;
        }

        $allowedRoles = array_map(fn (string $role): UserRole => UserRole::from($role), $roles);

        abort_unless(in_array($user->role, $allowedRoles, true), Response::HTTP_FORBIDDEN, 'You do not have permission to access this resource.');

        return $next($request);
    }
}
