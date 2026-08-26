<?php

namespace App\Http\Controllers;

use App\Enums\PayrollBatchStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\PayrollBatch;
use App\Models\Payslip;
use App\Models\PayslipTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $metrics = match ($user->role) {
            UserRole::SystemAdmin => [
                ['label' => '登録会社', 'value' => Company::count()],
                ['label' => '会社管理者', 'value' => User::where('role', UserRole::CompanyAdmin)->count()],
                ['label' => '帳票テンプレート', 'value' => PayslipTemplate::count()],
            ],
            UserRole::CompanyAdmin => [
                ['label' => '社員', 'value' => User::where('company_id', $user->company_id)->where('role', UserRole::Employee)->count()],
                ['label' => '公開待ち', 'value' => PayrollBatch::where('company_id', $user->company_id)->where('status', PayrollBatchStatus::Scheduled)->count()],
                ['label' => '未閲覧明細', 'value' => Payslip::whereHas('batch', fn ($query) => $query->where('company_id', $user->company_id)->where('status', PayrollBatchStatus::Published))->whereNull('first_viewed_at')->count()],
            ],
            UserRole::Employee => [
                ['label' => '公開済み明細', 'value' => Payslip::where('employee_id', $user->id)->whereHas('batch', fn ($query) => $query->where('status', PayrollBatchStatus::Published))->count()],
                ['label' => '未閲覧', 'value' => Payslip::where('employee_id', $user->id)->whereNull('first_viewed_at')->whereHas('batch', fn ($query) => $query->where('status', PayrollBatchStatus::Published))->count()],
                ['label' => '所属', 'value' => $user->department?->name ?? '未設定'],
            ],
        };

        return view('dashboard', compact('metrics'));
    }
}
