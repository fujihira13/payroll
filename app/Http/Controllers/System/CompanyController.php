<?php

namespace App\Http\Controllers\System;

use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $companies = Company::query()
            ->when($request->string('q')->isNotEmpty(), fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', '%'.$request->q.'%')
                ->orWhere('code', 'like', '%'.$request->q.'%')))
            ->withCount('users')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('system.companies.index', compact('companies'));
    }

    public function create(): View
    {
        return view('system.companies.form', ['company' => new Company]);
    }

    public function store(Request $request): RedirectResponse
    {
        $adminData = $request->validate([
            'initial_admin_login_id' => ['required', 'alpha_dash:ascii', 'max:50'],
            'initial_admin_name' => ['required', 'string', 'max:255'],
            'initial_admin_email' => ['required', 'email', 'unique:users,email'],
        ]);
        $temporaryPassword = Str::password(12, symbols: false);
        DB::transaction(function () use ($request, $adminData, $temporaryPassword) {
            $company = Company::create($this->validated($request));
            User::create([
                'company_id' => $company->id,
                'role' => UserRole::Employee,
                'permission' => UserPermission::CompanyManager,
                'employee_number' => 'ADMIN001',
                'login_id' => $adminData['initial_admin_login_id'],
                'name' => $adminData['initial_admin_name'],
                'email' => $adminData['initial_admin_email'],
                'password' => $temporaryPassword,
                'force_password_change' => true,
                'is_active' => true,
            ]);
        });

        return redirect()->route('manage.companies.index')->with('success', '会社と初期社員管理者を登録しました。')
            ->with('temporary_password', $temporaryPassword);
    }

    public function edit(Company $company): View
    {
        return view('system.companies.form', compact('company'));
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $company->update($this->validated($request, $company));

        return redirect()->route('manage.companies.index')->with('success', '会社情報を更新しました。');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $company->update(['is_active' => false]);
        $company->delete();

        return back()->with('success', '会社を停止しました。');
    }

    private function validated(Request $request, ?Company $company = null): array
    {
        return $request->validate([
            'code' => ['required', 'alpha_dash', 'max:30', Rule::unique('companies')->ignore($company)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
