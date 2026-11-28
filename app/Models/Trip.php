<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    protected $fillable = [
        'route_id',
        'bus_id',
        'departure_at',
        'arrival_at',
        'base_price',
        'currency',
        'status',
    ];

    protected $casts = [
        'departure_at' => 'datetime',
        'arrival_at'   => 'datetime',
        'amenities'    => 'array',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function bus(): BelongsTo
    {
        return $this->belongsTo(Bus::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(TripSeat::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
