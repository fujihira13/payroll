<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayslipTemplate extends Model
{
    protected $fillable = ['created_by', 'name', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayslipTemplateItem::class)->orderBy('sort_order');
    }

    public function companySettings(): HasMany
    {
        return $this->hasMany(CompanyPayslipSetting::class);
    }
}
