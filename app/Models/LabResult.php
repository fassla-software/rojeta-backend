<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabResult extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_normal' => 'boolean',
        ];
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }
}
