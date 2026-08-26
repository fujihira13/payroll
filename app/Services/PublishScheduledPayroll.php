<?php

namespace App\Services;

use App\Enums\PayrollBatchStatus;
use App\Mail\PayslipPublishedMail;
use App\Models\PayrollBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PublishScheduledPayroll
{
    public function handle(): int
    {
        $batchIds = PayrollBatch::where('status', PayrollBatchStatus::Scheduled)
            ->where('scheduled_for', '<=', now())->pluck('id');
        $published = 0;

        foreach ($batchIds as $batchId) {
            $batch = DB::transaction(function () use ($batchId) {
                $batch = PayrollBatch::lockForUpdate()->find($batchId);
                if (! $batch || $batch->status !== PayrollBatchStatus::Scheduled || $batch->scheduled_for->isFuture()) {
                    return null;
                }
                $batch->update(['status' => PayrollBatchStatus::Published, 'published_at' => now()]);

                return $batch->load(['company', 'payslips.employee']);
            });
            if (! $batch) {
                continue;
            }

            foreach ($batch->payslips as $payslip) {
                try {
                    Mail::to($payslip->employee->email)->send(new PayslipPublishedMail($payslip));
                    $payslip->update(['notified_at' => now()]);
                } catch (Throwable $exception) {
                    report($exception);
                }
            }
            $published++;
        }

        return $published;
    }
}
