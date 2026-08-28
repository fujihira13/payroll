<?php

namespace App\Http\Controllers\Company;

use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Mail\EmployeeNoticeMail;
use App\Models\Department;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\CsvReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $employees = User::where('company_id', $request->user()->company_id)
            ->with('department')
            ->when($request->string('q')->isNotEmpty(), fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', '%'.$request->q.'%')
                ->orWhere('employee_number', 'like', '%'.$request->q.'%')
                ->orWhere('login_id', 'like', '%'.$request->q.'%')
                ->orWhere('email', 'like', '%'.$request->q.'%')))
            ->orderBy('employee_number')->paginate(20)->withQueryString();
        $emailTemplates = EmailTemplate::where('company_id', $request->user()->company_id)
            ->where('is_active', true)->orderBy('name')->get();

        return view('company.employees.index', compact('employees', 'emailTemplates'));
    }

    public function create(Request $request): View
    {
        return view('company.employees.form', ['employee' => new User, 'departments' => $this->departments($request)]);
    }

    public function store(Request $request): RedirectResponse
    {
        User::create($this->validated($request) + [
            'company_id' => $request->user()->company_id,
            'role' => UserRole::Employee,
        ]);

        return redirect()->route('company.employees.index')->with('success', '社員を登録しました。');
    }

    public function edit(Request $request, User $employee): View
    {
        $this->ensureTenant($request, $employee);

        return view('company.employees.form', ['employee' => $employee, 'departments' => $this->departments($request)]);
    }

    public function update(Request $request, User $employee): RedirectResponse
    {
        $this->ensureTenant($request, $employee);
        $data = $this->validated($request, $employee);
        if ($employee->is($request->user()) && (int) $data['permission'] !== UserPermission::CompanyManager->value) {
            return back()->withErrors(['permission' => '自分自身のパーミッションを一般社員へ変更できません。'])->withInput();
        }
        $employee->update($data + ['role' => UserRole::Employee]);

        return redirect()->route('company.employees.index')->with('success', '社員情報を更新しました。');
    }

    public function destroy(Request $request, User $employee): RedirectResponse
    {
        $this->ensureTenant($request, $employee);
        if ($employee->is($request->user())) {
            return back()->withErrors(['employee' => '自分自身は削除できません。']);
        }
        if ($employee->payslips()->exists()) {
            $employee->update(['is_active' => false]);

            return back()->with('success', '給与明細を保持するため、社員を利用停止にしました。');
        }
        $employee->delete();

        return back()->with('success', '社員を削除しました。');
    }

    public function resetPassword(Request $request, User $employee): RedirectResponse
    {
        $this->ensureTenant($request, $employee);
        $temporaryPassword = Str::password(12, symbols: false);
        $employee->update([
            'password' => $temporaryPassword,
            'force_password_change' => true,
            'lock_status' => false,
            'login_failure_count' => 0,
        ]);

        return back()->with('success', '仮パスワードを再発行し、ロックを解除しました。')
            ->with('temporary_password', $temporaryPassword);
    }

    public function unlock(Request $request, User $employee): RedirectResponse
    {
        $this->ensureTenant($request, $employee);
        $employee->update(['lock_status' => false, 'login_failure_count' => 0]);

        return back()->with('success', 'ロックを解除しました。');
    }

    public function sendEmail(Request $request, User $employee): RedirectResponse
    {
        $this->ensureTenant($request, $employee);
        $data = $request->validate([
            'email_template_id' => ['required', Rule::exists('email_templates', 'id')->where('company_id', $request->user()->company_id)->where('is_active', true)],
        ]);
        $template = EmailTemplate::findOrFail($data['email_template_id']);
        Mail::to($employee->email)->send(new EmployeeNoticeMail($employee, $template));

        return back()->with('success', $employee->name.'さんへメールを送信しました。');
    }

    public function import(Request $request, CsvReader $reader): RedirectResponse
    {
        $request->validate(['csv' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        try {
            $rows = $reader->rows($request->file('csv'));
            $required = ['employee_number', 'name', 'email', 'department_code'];
            if ($rows && array_diff($required, array_keys($rows[0]))) {
                throw new RuntimeException('必須列は employee_number, name, email, department_code です。');
            }
            DB::transaction(function () use ($rows, $request) {
                foreach ($rows as $index => $row) {
                    $department = Department::where('company_id', $request->user()->company_id)->where('code', $row['department_code'])->first();
                    if (! $department) {
                        throw new RuntimeException(($index + 2).'行目: 部署コードが見つかりません。');
                    }
                    $employee = User::where('company_id', $request->user()->company_id)->where('employee_number', $row['employee_number'])->first();
                    $emailOwner = User::where('email', $row['email'])
                        ->when($employee, fn ($query) => $query->whereKeyNot($employee->id))->exists();
                    if ($emailOwner) {
                        throw new RuntimeException(($index + 2).'行目: メールアドレスは既に使用されています。');
                    }
                    if (! $employee && blank($row['password'] ?? null)) {
                        throw new RuntimeException(($index + 2).'行目: 新規社員には password が必要です。');
                    }
                    $loginId = $row['login_id'] ?? $row['employee_number'];
                    $loginOwner = User::where('company_id', $request->user()->company_id)->where('login_id', $loginId)
                        ->when($employee, fn ($query) => $query->whereKeyNot($employee->id))->exists();
                    if ($loginOwner) {
                        throw new RuntimeException(($index + 2).'行目: ログインIDは同じ会社内で既に使用されています。');
                    }
                    $permission = $this->permissionFromCsv($row, $index);
                    User::updateOrCreate(
                        ['company_id' => $request->user()->company_id, 'employee_number' => $row['employee_number']],
                        [
                            'department_id' => $department->id,
                            'role' => UserRole::Employee,
                            'permission' => $permission,
                            'login_id' => $loginId,
                            'name' => $row['name'],
                            'email' => $row['email'],
                            'password' => $employee ? $employee->password : $row['password'],
                            'is_active' => true,
                        ]
                    );
                }
            });
        } catch (RuntimeException $exception) {
            return back()->withErrors(['csv' => $exception->getMessage()]);
        }

        return back()->with('success', count($rows).'件の社員情報を取り込みました。');
    }

    public function csvTemplate()
    {
        return response()->streamDownload(function () {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['employee_number', 'login_id', 'name', 'email', 'department_code', 'permission', 'password']);
            fputcsv($stream, ['E001', 'staff001', '山田 太郎', 'taro@example.test', 'SALES', UserPermission::Employee->value, 'ChangeMe123!']);
            fclose($stream);
        }, 'employees-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validated(Request $request, ?User $employee = null): array
    {
        $companyId = $request->user()->company_id;
        $data = $request->validate([
            'employee_number' => ['required', 'max:50', Rule::unique('users')->where('company_id', $companyId)->ignore($employee)],
            'login_id' => ['required', 'alpha_dash:ascii', 'max:50', Rule::unique('users')->where('company_id', $companyId)->ignore($employee)],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('company_id', $companyId)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($employee)],
            'password' => [$employee ? 'nullable' : 'required', 'string', 'min:8'],
            'permission' => ['required', 'integer', Rule::in(array_column(UserPermission::cases(), 'value'))],
            'is_active' => ['required', 'boolean'],
        ]);
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $data;
    }

    private function permissionFromCsv(array $row, int $index): int
    {
        if (filled($row['permission'] ?? null)) {
            $permission = (int) $row['permission'];
        } else {
            // Keep imports made from the immediately previous local CSV format compatible.
            $permission = filter_var($row['is_company_admin'] ?? false, FILTER_VALIDATE_BOOL)
                ? UserPermission::CompanyManager->value
                : UserPermission::Employee->value;
        }
        if (! in_array($permission, array_column(UserPermission::cases(), 'value'), true)) {
            throw new RuntimeException(($index + 2).'行目: permission は1（一般社員）または9（社員管理者）で指定してください。');
        }

        return $permission;
    }

    private function departments(Request $request)
    {
        return Department::where('company_id', $request->user()->company_id)->orderBy('code')->get();
    }

    private function ensureTenant(Request $request, User $employee): void
    {
        abort_unless($employee->company_id === $request->user()->company_id, 404);
    }
}
