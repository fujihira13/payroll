<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    protected $fillable = ['company_id', 'type', 'subject', 'body'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
