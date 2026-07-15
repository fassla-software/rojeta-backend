<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_urgent' => 'boolean',
            'is_online' => 'boolean',
            'consultation_fee' => 'decimal:2',
            'original_fee' => 'decimal:2',
        ];
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class);
    }

    public function hospital()
    {
        return $this->belongsTo(User::class, 'hospital_id');
    }
}
