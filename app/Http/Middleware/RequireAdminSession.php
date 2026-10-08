<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequireAdminSession
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('web')->user();
        if (! $user) {
            return redirect()->guest(route('admin.login'));
        }
        abort_unless($user->role?->role_type === 'Admin', 403);

        return $next($request);
    }
}
