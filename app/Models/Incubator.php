<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incubator extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'monitoring_type' => 'array',
            'temperature' => 'decimal:1',
        ];
    }

    public function hospital()
    {
        return $this->belongsTo(User::class, 'hospital_id');
    }
}
