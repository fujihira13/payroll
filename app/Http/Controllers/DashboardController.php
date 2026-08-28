<?php

namespace App\Http\Controllers;

use App\Enums\PayrollBatchStatus;
use App\Models\PayrollBatch;
use App\Models\Payslip;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $metrics = $user->canManageCompany() ? [
            ['label' => '社員', 'value' => User::where('company_id', $user->company_id)->count()],
            ['label' => '公開待ち', 'value' => PayrollBatch::where('company_id', $user->company_id)->where('status', PayrollBatchStatus::Scheduled)->count()],
            ['label' => '未閲覧明細', 'value' => Payslip::whereHas('batch', fn ($query) => $query->where('company_id', $user->company_id)->where('status', PayrollBatchStatus::Published))->whereNull('first_viewed_at')->count()],
        ] : [
            ['label' => '公開済み明細', 'value' => Payslip::where('employee_id', $user->id)->whereHas('batch', fn ($query) => $query->where('status', PayrollBatchStatus::Published))->count()],
            ['label' => '未閲覧', 'value' => Payslip::where('employee_id', $user->id)->whereNull('first_viewed_at')->whereHas('batch', fn ($query) => $query->where('status', PayrollBatchStatus::Published))->count()],
            ['label' => '所属', 'value' => $user->department?->name ?? '未設定'],
        ];

        return view('dashboard', compact('metrics'));
    }
}
