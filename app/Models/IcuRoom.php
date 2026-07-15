<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IcuRoom extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['equipment_list' => 'array'];
    }

    public function hospital()
    {
        return $this->belongsTo(User::class, 'hospital_id');
    }
}
