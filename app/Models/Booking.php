<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $fillable = [
        'code',
        'user_id',
        'trip_id',
        'boarding_stop_id',
        'dropping_stop_id',
        'contact_name',
        'contact_email',
        'contact_phone',
        'total_amount',
        'currency',
        'status',
        'expires_at',
    ];

    protected $casts = ['expires_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function boardingStop(): BelongsTo
    {
        return $this->belongsTo(BusStop::class, 'boarding_stop_id');
    }

    public function droppingStop(): BelongsTo
    {
        return $this->belongsTo(BusStop::class, 'dropping_stop_id');
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(BookingPassenger::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
