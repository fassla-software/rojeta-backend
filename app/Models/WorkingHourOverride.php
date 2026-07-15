<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkingHourOverride extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_closed' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
