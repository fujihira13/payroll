<?php

namespace App\Models;

use App\Enums\PayslipItemCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyPayslipItem extends Model
{
    protected $fillable = [
        'company_payslip_setting_id', 'source_template_item_id', 'code', 'label', 'category',
        'data_type', 'sort_order', 'slot_code', 'is_required', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'category' => PayslipItemCategory::class,
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(CompanyPayslipSetting::class, 'company_payslip_setting_id');
    }
}
