<?php

namespace App\Http\Controllers\Employee;

use App\Enums\PayrollBatchStatus;
use App\Http\Controllers\Controller;
use App\Models\Payslip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;

class PayslipController extends Controller
{
    public function index(Request $request): View
    {
        $payslips = Payslip::where('employee_id', $request->user()->id)
            ->whereHas('batch', fn ($query) => $query->where('status', PayrollBatchStatus::Published))
            ->with('batch')->latest()->paginate(12);

        return view('employee.payslips.index', compact('payslips'));
    }

    public function show(Request $request, Payslip $payslip): View
    {
        $this->authorizeEmployee($request, $payslip);
        $this->markViewed($payslip);
        $payslip->load(['batch.company', 'employee.department']);

        return view('employee.payslips.show', compact('payslip'));
    }

    public function pdf(Request $request, Payslip $payslip)
    {
        $this->authorizeEmployee($request, $payslip);
        $this->markViewed($payslip);
        $payslip->load(['batch.company', 'employee.department']);

        $temporaryDirectory = storage_path('app/mpdf');
        File::ensureDirectoryExists($temporaryDirectory);
        $defaultConfig = (new ConfigVariables)->getDefaults();
        $fontConfig = (new FontVariables)->getDefaults();
        $mpdf = new Mpdf([
            'mode' => 'ja',
            'format' => 'A4',
            'tempDir' => $temporaryDirectory,
            'fontDir' => array_merge($defaultConfig['fontDir'], ['/usr/share/fonts/opentype/ipafont-gothic']),
            'fontdata' => $fontConfig['fontdata'] + ['ipag' => ['R' => 'ipag.ttf']],
            'default_font' => 'ipag',
        ]);
        $mpdf->WriteHTML(view('employee.payslips.pdf', compact('payslip'))->render());
        $content = $mpdf->Output('', Destination::STRING_RETURN);
        $filename = 'payslip-'.$payslip->batch->target_month->format('Y-m').'.pdf';

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function authorizeEmployee(Request $request, Payslip $payslip): void
    {
        $payslip->loadMissing('batch');
        abort_unless(
            $payslip->employee_id === $request->user()->id
            && $payslip->batch->status === PayrollBatchStatus::Published,
            404
        );
    }

    private function markViewed(Payslip $payslip): void
    {
        $payslip->forceFill([
            'first_viewed_at' => $payslip->first_viewed_at ?? now(),
            'last_viewed_at' => now(),
            'view_count' => $payslip->view_count + 1,
        ])->save();
    }
}
