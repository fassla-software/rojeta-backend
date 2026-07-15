<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaboratoryProfile extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'is_verified' => 'boolean',
            'home_sample_collection' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
