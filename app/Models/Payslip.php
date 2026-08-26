<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    protected $fillable = [
        'payroll_batch_id', 'employee_id', 'details', 'gross_amount', 'deduction_amount',
        'net_amount', 'first_viewed_at', 'last_viewed_at', 'view_count', 'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'gross_amount' => 'decimal:2',
            'deduction_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'first_viewed_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PayrollBatch::class, 'payroll_batch_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
