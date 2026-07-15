<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NursingProfile extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
