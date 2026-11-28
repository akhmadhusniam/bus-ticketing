<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusLayout extends Model
{
    protected $fillable = [
        'name',
        'decks',
        'total_seats',
        'seat_map',
    ];

    protected $casts = ['seat_map' => 'array'];

    public function buses(): HasMany
    {
        return $this->hasMany(Bus::class);
    }
}
