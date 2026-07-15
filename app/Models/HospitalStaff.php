<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HospitalStaff extends Model
{
    protected $table = 'hospital_staff';

    protected $guarded = [];

    public function specialty()
    {
        return $this->belongsTo(HospitalSpecialty::class, 'specialty_id');
    }
}
