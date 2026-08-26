<?php

namespace App\Services;

use App\Enums\PayrollBatchStatus;
use App\Enums\PayslipItemCategory;
use App\Enums\UserRole;
use App\Models\PayrollBatch;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayrollCsvImporter
{
    public function __construct(private readonly CsvReader $reader) {}

    public function import(PayrollBatch $batch, UploadedFile $file): int
    {
        if ($batch->status !== PayrollBatchStatus::Draft) {
            throw new RuntimeException('下書き状態のデータだけ取り込めます。');
        }

        $items = $batch->setting->items()->where('is_active', true)->orderBy('sort_order')->get();
        $rows = $this->reader->rows($file);
        if ($rows === []) {
            throw new RuntimeException('CSVにデータ行がありません。');
        }

        $requiredHeaders = array_merge(['employee_number'], $items->pluck('code')->all());
        $missing = array_diff($requiredHeaders, array_keys($rows[0]));
        if ($missing) {
            throw new RuntimeException('不足している列: '.implode(', ', $missing));
        }

        $prepared = [];
        foreach ($rows as $rowIndex => $row) {
            $line = $rowIndex + 2;
            $employee = User::where('company_id', $batch->company_id)
                ->where('role', UserRole::Employee)
                ->where('employee_number', $row['employee_number'])
                ->first();
            if (! $employee) {
                throw new RuntimeException("{$line}行目: 社員番号 {$row['employee_number']} が見つかりません。");
            }

            $details = [];
            $gross = 0.0;
            $deductions = 0.0;
            foreach ($items as $item) {
                $value = $row[$item->code] ?? '';
                if ($item->is_required && $value === '') {
                    throw new RuntimeException("{$line}行目: {$item->label} は必須です。");
                }
                if (in_array($item->data_type, ['amount', 'number'], true) && $value !== '' && ! is_numeric(str_replace(',', '', $value))) {
                    throw new RuntimeException("{$line}行目: {$item->label} は数値で入力してください。");
                }
                $normalized = in_array($item->data_type, ['amount', 'number'], true)
                    ? (float) str_replace(',', '', $value ?: '0')
                    : $value;

                if ($item->data_type === 'amount' && $item->category === PayslipItemCategory::Earning) {
                    $gross += $normalized;
                }
                if ($item->data_type === 'amount' && $item->category === PayslipItemCategory::Deduction) {
                    $deductions += $normalized;
                }
                $details[] = [
                    'code' => $item->code,
                    'label' => $item->label,
                    'category' => $item->category->value,
                    'data_type' => $item->data_type,
                    'value' => $normalized,
                ];
            }

            $prepared[] = compact('employee', 'details', 'gross', 'deductions');
        }

        DB::transaction(function () use ($batch, $prepared) {
            foreach ($prepared as $row) {
                $batch->payslips()->updateOrCreate(
                    ['employee_id' => $row['employee']->id],
                    [
                        'details' => $row['details'],
                        'gross_amount' => $row['gross'],
                        'deduction_amount' => $row['deductions'],
                        'net_amount' => $row['gross'] - $row['deductions'],
                    ]
                );
            }
        });

        return count($prepared);
    }
}
