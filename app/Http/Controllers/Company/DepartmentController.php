<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $departments = Department::where('company_id', $request->user()->company_id)
            ->withCount('users')->orderBy('code')->paginate(15);

        return view('company.departments.index', compact('departments'));
    }

    public function create(): View
    {
        return view('company.departments.form', ['department' => new Department]);
    }

    public function store(Request $request): RedirectResponse
    {
        Department::create($this->validated($request) + ['company_id' => $request->user()->company_id]);

        return redirect()->route('company.departments.index')->with('success', '部署を登録しました。');
    }

    public function edit(Request $request, Department $department): View
    {
        $this->ensureTenant($request, $department);

        return view('company.departments.form', compact('department'));
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $this->ensureTenant($request, $department);
        $department->update($this->validated($request, $department));

        return redirect()->route('company.departments.index')->with('success', '部署を更新しました。');
    }

    public function destroy(Request $request, Department $department): RedirectResponse
    {
        $this->ensureTenant($request, $department);
        if ($department->users()->exists()) {
            return back()->withErrors(['department' => '所属社員がいるため削除できません。']);
        }
        $department->delete();

        return back()->with('success', '部署を削除しました。');
    }

    private function validated(Request $request, ?Department $department = null): array
    {
        return $request->validate([
            'code' => ['required', 'alpha_dash', 'max:30', Rule::unique('departments')->where('company_id', $request->user()->company_id)->ignore($department)],
            'name' => ['required', 'string', 'max:255'],
        ]);
    }

    private function ensureTenant(Request $request, Department $department): void
    {
        abort_unless($department->company_id === $request->user()->company_id, 404);
    }
}
