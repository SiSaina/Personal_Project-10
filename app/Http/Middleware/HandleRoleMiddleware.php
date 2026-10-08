<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();
        // If the user is not authenticated
        if (! $user) {
            return response()->json([
                'message' => 'Please log in to access this resource.',
            ], 401);
        }

        // If the user doesn't have the correct role to certain CRUD
        if (!$user->role || !in_array($user->role->role_type, $roles)) {
            return response()->json([
                'message' => 'You do not have permission to access this resource.',
            ], 403);
        }

        return $next($request);
    }
}
