<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorVacation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
