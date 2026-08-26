<?php

namespace App\Models;

use App\Enums\PayslipItemCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipTemplateItem extends Model
{
    protected $fillable = [
        'payslip_template_id', 'code', 'label', 'category', 'data_type', 'sort_order', 'is_required',
    ];

    protected function casts(): array
    {
        return ['category' => PayslipItemCategory::class, 'is_required' => 'boolean'];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PayslipTemplate::class, 'payslip_template_id');
    }
}
