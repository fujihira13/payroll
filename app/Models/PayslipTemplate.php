<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayslipTemplate extends Model
{
    protected $fillable = ['created_by', 'created_by_admin_id', 'name', 'description', 'layout_type', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function adminCreator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
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
