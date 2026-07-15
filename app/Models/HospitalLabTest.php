<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HospitalLabTest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'home_collection_available' => 'boolean',
        ];
    }

    public function hospital()
    {
        return $this->belongsTo(User::class, 'hospital_id');
    }
}
