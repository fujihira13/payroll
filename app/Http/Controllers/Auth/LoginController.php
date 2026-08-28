<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(Request $request): View
    {
        $companyCode = $request->route('companyCode');
        $company = $companyCode ? Company::where(fn ($query) => $query
            ->where('code', $companyCode)->orWhere('login_slug', $companyCode))->firstOrFail() : null;

        return view('auth.login', compact('company'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_code' => ['required', 'string'],
            'login_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $company = Company::where(fn ($query) => $query
            ->where('code', $data['company_code'])
            ->orWhere('login_slug', $data['company_code']))
            ->where('is_active', true)->first();

        if (! $company) {
            return back()->withErrors(['company_code' => '会社コードまたはログイン情報が正しくありません。'])
                ->onlyInput('company_code', 'login_id');
        }

        $credentials = [
            'company_id' => $company->id,
            'login_id' => $data['login_id'],
            'password' => $data['password'],
        ];

        $user = User::withTrashed()->where('company_id', $company->id)->where('login_id', $data['login_id'])->first();

        if ($user?->lock_status) {
            return back()->withErrors(['login_id' => 'アカウントがロックされています。社員管理者へ連絡してください。'])
                ->onlyInput('company_code', 'login_id');
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            if ($user && ! $user->trashed() && $user->is_active) {
                $failureCount = $user->login_failure_count + 1;
                $user->update(['login_failure_count' => $failureCount, 'lock_status' => $failureCount >= 5]);
            }

            return back()->withErrors(['login_id' => '会社コード、ログインIDまたはパスワードが正しくありません。'])
                ->onlyInput('company_code', 'login_id');
        }

        $request->session()->regenerate();
        $authenticatedUser = $request->user();

        if (! $authenticatedUser->is_active || ($authenticatedUser->company && ! $authenticatedUser->company->is_active)) {
            Auth::logout();
            $request->session()->invalidate();

            return back()->withErrors(['email' => 'このアカウントは現在利用できません。']);
        }

        $authenticatedUser->update(['login_failure_count' => 0, 'lock_status' => false, 'last_login_at' => now()]);

        return redirect()->intended($authenticatedUser->force_password_change
            ? route('account.password.edit')
            : route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
