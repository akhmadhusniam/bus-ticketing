<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripSeat extends Model
{
    protected $fillable = [
        'trip_id',
        'seat_label',
        'deck',
        'price',
        'status',
        'locked_booking_id',
        'locked_until',
    ];

    protected $casts = ['locked_until' => 'datetime'];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
