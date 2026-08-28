<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyPayslipSetting extends Model
{
    protected $fillable = ['company_id', 'payslip_template_id', 'configured_by', 'name', 'layout_type', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PayslipTemplate::class, 'payslip_template_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CompanyPayslipItem::class)->orderBy('sort_order');
    }
}
