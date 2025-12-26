<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle($request, Closure $next, $role)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        if (!Auth::user()->hasRole($role)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}

