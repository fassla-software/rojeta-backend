<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    protected $guarded = [];

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
