<?php

namespace App\Http\Controllers\System;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdministratorController extends Controller
{
    public function index(Request $request): View
    {
        $administrators = User::where('role', UserRole::CompanyAdmin)
            ->with('company')
            ->when($request->string('q')->isNotEmpty(), fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', '%'.$request->q.'%')
                ->orWhere('email', 'like', '%'.$request->q.'%')))
            ->latest()->paginate(15)->withQueryString();

        return view('system.administrators.index', compact('administrators'));
    }

    public function create(): View
    {
        return view('system.administrators.form', [
            'administrator' => new User,
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['role'] = UserRole::CompanyAdmin;
        User::create($data);

        return redirect()->route('system.administrators.index')->with('success', '会社管理者を登録しました。');
    }

    public function edit(User $administrator): View
    {
        abort_unless($administrator->hasRole(UserRole::CompanyAdmin), 404);

        return view('system.administrators.form', [
            'administrator' => $administrator,
            'companies' => Company::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $administrator): RedirectResponse
    {
        abort_unless($administrator->hasRole(UserRole::CompanyAdmin), 404);
        $administrator->update($this->validated($request, $administrator));

        return redirect()->route('system.administrators.index')->with('success', '会社管理者を更新しました。');
    }

    public function destroy(User $administrator): RedirectResponse
    {
        abort_unless($administrator->hasRole(UserRole::CompanyAdmin), 404);
        $administrator->update(['is_active' => false]);
        $administrator->delete();

        return back()->with('success', '会社管理者を停止しました。');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $rules = [
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'is_active' => ['required', 'boolean'],
        ];
        $data = $request->validate($rules);
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $data;
    }
}
