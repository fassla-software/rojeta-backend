<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NursingStaff extends Model
{
    protected $table = 'nursing_staff';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['skills' => 'array'];
    }

    public function nursingOffice()
    {
        return $this->belongsTo(User::class, 'nursing_office_id');
    }
}
