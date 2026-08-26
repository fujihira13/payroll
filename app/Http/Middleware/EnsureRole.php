<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active || ! in_array($user->role->value, $roles, true)) {
            abort(403);
        }

        if ($user->company && ! $user->company->is_active) {
            abort(403, '会社アカウントが停止されています。');
        }

        return $next($request);
    }
}
