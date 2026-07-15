<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'service_fee' => 'decimal:2',
            'platform_commission' => 'decimal:2',
            'earning' => 'decimal:2',
        ];
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
