<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'photos' => 'array',
            'working_hours' => 'array',
        ];
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function services()
    {
        return $this->hasMany(ClinicService::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
