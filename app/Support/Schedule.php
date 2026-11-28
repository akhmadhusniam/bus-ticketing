<?php

namespace App\Support;

use App\Models\RouteStop;
use App\Models\Trip;
use Illuminate\Support\Carbon;

class Schedule
{
    /** Waktu suatu titik dalam timezone kota titik tersebut. */
    public static function timeAt(Trip $trip, RouteStop $stop): Carbon
    {
        $timezone = $stop->busStop?->city?->timezone
            ?: $trip->route->origin?->timezone
            ?: env('APP_DISPLAY_TIMEZONE', 'Asia/Jakarta');

        return $trip->departure_at->copy()
            ->addMinutes((int) $stop->offset_minutes)
            ->setTimezone($timezone);
    }

    public static function stopName(RouteStop $stop): string
    {
        return $stop->busStop?->name ?? 'Titik pemberhentian';
    }

    public static function cityName(RouteStop $stop): string
    {
        return $stop->busStop?->city?->name ?? '';
    }
}
