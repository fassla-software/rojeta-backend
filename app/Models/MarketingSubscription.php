<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingSubscription extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function package()
    {
        return $this->belongsTo(MarketingPackage::class, 'package_id');
    }
}
