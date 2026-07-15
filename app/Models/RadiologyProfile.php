<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RadiologyProfile extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_verified' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
