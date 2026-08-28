<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdministratorController extends Controller
{
    public function index(Request $request): View
    {
        $administrators = Admin::query()
            ->when($request->string('q')->isNotEmpty(), fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', '%'.$request->q.'%')
                ->orWhere('login_id', 'like', '%'.$request->q.'%')))
            ->latest()->paginate(15)->withQueryString();

        return view('system.administrators.index', compact('administrators'));
    }

    public function create(): View
    {
        return view('system.administrators.form', ['administrator' => new Admin]);
    }

    public function store(Request $request): RedirectResponse
    {
        $temporaryPassword = $this->temporaryPassword();
        Admin::create($this->validated($request) + [
            'password' => $temporaryPassword,
            'force_password_change' => true,
            'is_active' => true,
        ]);

        return redirect()->route('manage.admins.index')->with('success', 'システム管理者を登録しました。')
            ->with('temporary_password', $temporaryPassword);
    }

    public function edit(Admin $administrator): View
    {
        return view('system.administrators.form', compact('administrator'));
    }

    public function update(Request $request, Admin $administrator): RedirectResponse
    {
        $data = $this->validated($request, $administrator);
        if ($administrator->is(auth('admin')->user()) && ! (bool) $data['is_active']) {
            return back()->withErrors(['is_active' => '自分自身を利用停止にはできません。'])->withInput();
        }
        $administrator->update($data);

        return redirect()->route('manage.admins.index')->with('success', 'システム管理者を更新しました。');
    }

    public function resetPassword(Admin $administrator): RedirectResponse
    {
        $temporaryPassword = $this->temporaryPassword();
        $administrator->update([
            'password' => $temporaryPassword,
            'force_password_change' => true,
            'lock_status' => false,
            'try_count' => 0,
            'is_active' => true,
        ]);

        return back()->with('success', '仮パスワードを再発行し、ロックを解除しました。')
            ->with('temporary_password', $temporaryPassword);
    }

    public function unlock(Admin $administrator): RedirectResponse
    {
        $administrator->update(['lock_status' => false, 'try_count' => 0]);

        return back()->with('success', 'アカウントのロックを解除しました。');
    }

    public function destroy(Admin $administrator): RedirectResponse
    {
        if ($administrator->is(auth('admin')->user())) {
            return back()->withErrors(['administrator' => '自分自身は削除できません。']);
        }
        if ($administrator->is_active && Admin::where('is_active', true)->count() <= 1) {
            return back()->withErrors(['administrator' => '最後の有効なシステム管理者は削除できません。']);
        }
        $administrator->update(['is_active' => false]);
        $administrator->delete();

        return back()->with('success', 'システム管理者を停止しました。');
    }

    private function validated(Request $request, ?Admin $administrator = null): array
    {
        $loginIdRules = [
            'required',
            'string',
            'min:4',
            $administrator && $request->input('login_id') === $administrator->login_id ? 'max:255' : 'max:20',
            Rule::unique('admins')->ignore($administrator),
        ];
        if (! $administrator || $request->input('login_id') !== $administrator->login_id) {
            $loginIdRules[] = 'alpha_dash:ascii';
        }

        return $request->validate([
            'login_id' => $loginIdRules,
            'name' => ['required', 'string', 'max:40'],
            'is_active' => [$administrator ? 'required' : 'nullable', 'boolean'],
        ]);
    }

    private function temporaryPassword(): string
    {
        return Str::password(12, symbols: false);
    }
}
