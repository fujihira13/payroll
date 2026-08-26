<?php

namespace App\Models;

use App\Enums\PayrollBatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollBatch extends Model
{
    protected $fillable = [
        'company_id', 'company_payslip_setting_id', 'created_by', 'approved_by', 'name',
        'target_month', 'status', 'scheduled_for', 'approved_at', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PayrollBatchStatus::class,
            'target_month' => 'date',
            'scheduled_for' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(CompanyPayslipSetting::class, 'company_payslip_setting_id');
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }
}
