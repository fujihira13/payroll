<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.manage-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:admin'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        auth('admin')->user()->update([
            'password' => $data['password'],
            'force_password_change' => false,
            'try_count' => 0,
            'lock_status' => false,
        ]);

        return redirect()->route('manage.companies.index')->with('success', 'パスワードを変更しました。');
    }
}
