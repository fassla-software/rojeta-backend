<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabService extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_home_collection_available' => 'boolean',
        ];
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }
}
