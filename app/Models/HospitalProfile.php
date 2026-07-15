<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HospitalProfile extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'certificates' => 'array',
            'images' => 'array',
            'settings' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
