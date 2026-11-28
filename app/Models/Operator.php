<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Operator extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'contact_email',
        'contact_phone',
        'rating',
        'country_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'rating'    => 'float',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function buses(): HasMany
    {
        return $this->hasMany(Bus::class);
    }

    public function routes(): HasMany
    {
        return $this->hasMany(Route::class);
    }
}
