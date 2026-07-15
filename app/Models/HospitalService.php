<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HospitalService extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_enabled' => 'boolean'];
    }

    public function hospital()
    {
        return $this->belongsTo(User::class, 'hospital_id');
    }
}
