<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabTimeSlot extends Model
{
    protected $guarded = [];

    public function config()
    {
        return $this->belongsTo(LabHomeVisitConfig::class, 'config_id');
    }
}
