<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HospitalSpecialty extends Model
{
    protected $guarded = [];

    public function hospital()
    {
        return $this->belongsTo(User::class, 'hospital_id');
    }

    public function staff()
    {
        return $this->hasMany(HospitalStaff::class, 'specialty_id');
    }
}
