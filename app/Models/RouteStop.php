<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RouteStop extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'route_id',
        'bus_stop_id',
        'stop_order',
        'offset_minutes',
        'is_boarding',
        'is_dropping',
    ];

    protected $casts = [
        'is_boarding' => 'boolean',
        'is_dropping' => 'boolean',
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function busStop(): BelongsTo
    {
        return $this->belongsTo(BusStop::class);
    }
}
