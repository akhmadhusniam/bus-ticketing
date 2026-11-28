<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bus extends Model
{
    protected $fillable = [
        'operator_id',
        'bus_layout_id',
        'plate_number',
        'bus_type',
        'service_mode',
        'amenities',
        'is_active',
    ];

    protected $casts = [
        'amenities' => 'array',
        'is_active' => 'boolean',
    ];

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function layout(): BelongsTo
    {
        return $this->belongsTo(BusLayout::class, 'bus_layout_id');
    }
}
