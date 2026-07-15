<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingPackage extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'platforms' => 'array',
            'features' => 'array',
            'monthly_price' => 'decimal:2',
            'yearly_price' => 'decimal:2',
            'is_most_popular' => 'boolean',
        ];
    }

    public function subscriptions()
    {
        return $this->hasMany(MarketingSubscription::class, 'package_id');
    }
}
