<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!empty($roles) && !in_array($user->role, $roles, true)) {
            abort(403, 'Akses ditolak untuk peran ini.');
        }

        return $next($request);
    }
}
