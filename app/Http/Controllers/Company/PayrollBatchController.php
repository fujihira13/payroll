<?php

namespace App\Http\Controllers\Company;

use App\Enums\PayrollBatchStatus;
use App\Http\Controllers\Controller;
use App\Models\CompanyPayslipSetting;
use App\Models\PayrollBatch;
use App\Services\PayrollCsvImporter;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class PayrollBatchController extends Controller
{
    public function index(Request $request): View
    {
        $batches = PayrollBatch::where('company_id', $request->user()->company_id)
            ->withCount([
                'payslips',
                'payslips as viewed_count' => fn ($query) => $query->whereNotNull('first_viewed_at'),
            ])
            ->latest('target_month')->paginate(15);

        return view('company.payroll.index', compact('batches'));
    }

    public function create(Request $request): View
    {
        $settings = CompanyPayslipSetting::where('company_id', $request->user()->company_id)
            ->where('is_active', true)->with('items')->get();

        return view('company.payroll.create', compact('settings'));
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;
        $data = $request->validate([
            'company_payslip_setting_id' => ['required', Rule::exists('company_payslip_settings', 'id')->where('company_id', $companyId)->where('is_active', true)],
            'name' => ['required', 'string', 'max:255'],
            'target_month' => ['required', 'date_format:Y-m'],
        ]);
        $batch = PayrollBatch::create([
            ...$data,
            'target_month' => Carbon::createFromFormat('Y-m', $data['target_month'])->startOfMonth(),
            'company_id' => $companyId,
            'created_by' => $request->user()->id,
            'status' => PayrollBatchStatus::Draft,
        ]);

        return redirect()->route('company.payroll.show', $batch)->with('success', '給与明細の下書きを作成しました。CSVを取り込んでください。');
    }

    public function show(Request $request, PayrollBatch $batch): View
    {
        $this->ensureTenant($request, $batch);
        $batch->load(['setting.items', 'payslips.employee.department']);

        return view('company.payroll.show', compact('batch'));
    }

    public function import(Request $request, PayrollBatch $batch, PayrollCsvImporter $importer): RedirectResponse
    {
        $this->ensureTenant($request, $batch);
        $request->validate(['csv' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        try {
            $count = $importer->import($batch, $request->file('csv'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['csv' => $exception->getMessage()]);
        }

        return back()->with('success', "{$count}件の給与明細を取り込みました。");
    }

    public function approve(Request $request, PayrollBatch $batch): RedirectResponse
    {
        $this->ensureTenant($request, $batch);
        abort_unless($batch->status === PayrollBatchStatus::Draft, 422);
        if (! $batch->payslips()->exists()) {
            return back()->withErrors(['batch' => '給与明細を1件以上取り込んでください。']);
        }
        $data = $request->validate(['scheduled_for' => ['required', 'date', 'after_or_equal:now']]);
        $batch->update([
            'status' => PayrollBatchStatus::Scheduled,
            'scheduled_for' => $data['scheduled_for'],
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', '承認しました。指定日時に公開・通知されます。');
    }

    public function csvTemplate(Request $request, PayrollBatch $batch)
    {
        $this->ensureTenant($request, $batch);
        $items = $batch->setting->items()->where('is_active', true)->orderBy('sort_order')->get();

        return response()->streamDownload(function () use ($items) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, array_merge(['employee_number'], $items->pluck('code')->all()));
            fclose($stream);
        }, 'payroll-'.$batch->target_month->format('Y-m').'-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function ensureTenant(Request $request, PayrollBatch $batch): void
    {
        abort_unless($batch->company_id === $request->user()->company_id, 404);
    }
}
