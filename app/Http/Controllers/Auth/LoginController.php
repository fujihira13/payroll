<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::withTrashed()->where('email', $credentials['email'])->first();

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            if ($user && ! $user->trashed()) {
                $user->increment('login_failure_count');
            }

            return back()->withErrors(['email' => 'メールアドレスまたはパスワードが正しくありません。'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $authenticatedUser = $request->user();

        if (! $authenticatedUser->is_active || ($authenticatedUser->company && ! $authenticatedUser->company->is_active)) {
            Auth::logout();
            $request->session()->invalidate();

            return back()->withErrors(['email' => 'このアカウントは現在利用できません。']);
        }

        $authenticatedUser->update(['login_failure_count' => 0, 'last_login_at' => now()]);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
