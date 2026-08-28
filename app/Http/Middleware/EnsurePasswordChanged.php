<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next, string $guard = 'web'): Response
    {
        $user = auth($guard)->user();
        if ($user?->force_password_change) {
            return redirect()->route($guard === 'admin' ? 'manage.password.edit' : 'account.password.edit')
                ->with('warning', '初回ログインまたはパスワードリセット後のため、パスワードを変更してください。');
        }

        return $next($request);
    }
}
