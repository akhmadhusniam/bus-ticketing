<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Route as RouteModel;
use App\Models\Trip;
use Illuminate\Support\Carbon;

class RouteLandingController extends Controller
{
    public function show(string $originSlug, string $destinationSlug)
    {
        $origin = City::where('slug', $originSlug)->firstOrFail();
        $destination = City::where('slug', $destinationSlug)->firstOrFail();

        abort_if($origin->is($destination), 404);

        $timezone = $origin->timezone ?: env('APP_DISPLAY_TIMEZONE', 'Asia/Jakarta');
        $today = now($timezone)->toDateString();
        $start = Carbon::parse($today, $timezone)->startOfDay()->utc();
        $end = $start->copy()->addDays(7);

        $trips = Trip::query()
            ->select([
                'trips.*',
                'routes.duration_minutes',
                'routes.distance_km',
                'operators.name as operator_name',
            ])
            ->join('routes', 'routes.id', '=', 'trips.route_id')
            ->join('buses', 'buses.id', '=', 'trips.bus_id')
            ->join('operators', 'operators.id', '=', 'buses.operator_id')
            ->where('routes.origin_city_id', $origin->id)
            ->where('routes.destination_city_id', $destination->id)
            ->where('routes.is_active', true)
            ->where('buses.is_active', true)
            ->where('trips.status', 'scheduled')
            ->where('trips.departure_at', '>=', $start)
            ->where('trips.departure_at', '<', $end)
            ->with(['route.stops.busStop.city'])
            ->withCount(['seats as available_seats' => function ($query) {
                $query->where('status', 'available');
            }])
            ->orderBy('trips.departure_at')
            ->get();

        $related = RouteModel::query()
            ->where('origin_city_id', $origin->id)
            ->where('destination_city_id', '!=', $destination->id)
            ->where('is_active', true)
            ->with('destination')
            ->get()
            ->pluck('destination')
            ->unique('id')
            ->values();

        $faqs = [
            [
                'question' => "Berapa lama perjalanan bus {$origin->name} ke {$destination->name}?",
                'answer'   => $trips->isNotEmpty()
                    ? 'Rata-rata waktu tempuh sekitar '.(int) round($trips->avg('duration_minutes')).' menit, tergantung operator dan kondisi jalan.'
                    : 'Waktu tempuh berbeda untuk setiap operator. Lihat jadwal yang tersedia di halaman ini.',
            ],
            [
                'question' => "Berapa harga tiket bus {$origin->name} ke {$destination->name}?",
                'answer'   => $trips->isNotEmpty()
                    ? 'Harga mulai Rp '.number_format($trips->min('base_price'), 0, ',', '.').' per kursi.'
                    : 'Belum ada jadwal aktif untuk rute ini.',
            ],
            [
                'question' => 'Di mana titik naik dan turun busnya?',
                'answer'   => 'Setiap perjalanan memiliki titik naik dan turun yang berbeda. Buka detail perjalanan untuk melihat daftar lengkap halte dan jamnya.',
            ],
        ];

        $structured = [
            '@context' => 'https://schema.org',
            '@graph'   => [
                [
                    '@type'         => 'WebPage',
                    '@id'           => route('route.landing', ['origin' => $origin->slug, 'destination' => $destination->slug]),
                    'name'          => "Bus {$origin->name} ke {$destination->name}",
                    'description'   => "Jadwal, harga tiket, dan titik naik turun bus {$origin->name} - {$destination->name}.",
                    'inLanguage'    => 'id-ID',
                ],
                [
                    '@type'           => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Beranda', 'item' => route('home')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => $origin->name],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $destination->name],
                    ],
                ],
                [
                    '@type'      => 'FAQPage',
                    'mainEntity' => array_map(fn (array $faq) => [
                        '@type'          => 'Question',
                        'name'           => $faq['question'],
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text'  => $faq['answer'],
                        ],
                    ], $faqs),
                ],
            ],
        ];

        return view('routes.show', [
            'origin'      => $origin,
            'destination' => $destination,
            'trips'       => $trips,
            'related'     => $related,
            'faqs'        => $faqs,
            'structured'  => $structured,
            'timezone'    => $timezone,
            'today'       => $today,
        ]);
    }
}
