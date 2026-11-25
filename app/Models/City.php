<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = [
        'country_id',
        'name',
        'slug',
        'timezone',
        'latitude',
        'longitude',
        'is_popular',
    ];

    protected $casts = ['is_popular' => 'boolean'];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function busStops(): HasMany
    {
        return $this->hasMany(BusStop::class);
    }
}
