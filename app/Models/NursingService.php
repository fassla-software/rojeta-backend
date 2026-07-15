<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NursingService extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['bullet_points' => 'array'];
    }

    public function nursingOffice()
    {
        return $this->belongsTo(User::class, 'nursing_office_id');
    }
}
