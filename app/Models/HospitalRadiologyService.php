<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HospitalRadiologyService extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'contrast_agent_required' => 'boolean',
        ];
    }

    public function hospital()
    {
        return $this->belongsTo(User::class, 'hospital_id');
    }
}
