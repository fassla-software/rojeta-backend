<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabHomeVisitConfig extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_service_visible' => 'boolean',
            'collection_fee' => 'decimal:2',
            'minimum_booking_amount' => 'decimal:2',
        ];
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function serviceAreas()
    {
        return $this->hasMany(LabServiceArea::class, 'config_id');
    }

    public function timeSlots()
    {
        return $this->hasMany(LabTimeSlot::class, 'config_id');
    }
}
