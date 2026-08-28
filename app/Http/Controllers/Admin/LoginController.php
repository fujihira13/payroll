<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.manage-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'login_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);
        $admin = Admin::withTrashed()->where('login_id', $credentials['login_id'])->first();

        if (! $admin || $admin->trashed() || ! $admin->is_active) {
            return back()->withErrors(['login_id' => 'ログインIDまたはパスワードが正しくありません。'])->onlyInput('login_id');
        }
        if ($admin->lock_status) {
            return back()->withErrors(['login_id' => 'アカウントがロックされています。別のシステム管理者へ連絡してください。'])->onlyInput('login_id');
        }

        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            $tryCount = $admin->try_count + 1;
            $admin->update(['try_count' => $tryCount, 'lock_status' => $tryCount >= 5]);

            return back()->withErrors(['login_id' => $tryCount >= 5
                ? 'ログインに5回失敗したため、アカウントをロックしました。'
                : 'ログインIDまたはパスワードが正しくありません。'])->onlyInput('login_id');
        }

        $request->session()->regenerate();
        $admin->update(['try_count' => 0, 'last_login_at' => now()]);

        return redirect()->intended($admin->force_password_change
            ? route('manage.password.edit')
            : route('manage.companies.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('manage.login');
    }
}
