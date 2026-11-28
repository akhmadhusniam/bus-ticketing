<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Trip;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SearchController extends Controller
{
    private const SORTS = [
        ['value' => 'departure_asc', 'label' => 'Waktu berangkat'],
        ['value' => 'price_asc',     'label' => 'Harga termurah'],
        ['value' => 'price_desc',    'label' => 'Harga tertinggi'],
        ['value' => 'duration_asc',  'label' => 'Durasi tercepat'],
    ];

    private const BUS_TYPES = [
        ['value' => 'seater',       'label' => 'Seater'],
        ['value' => 'semi_sleeper', 'label' => 'Semi sleeper'],
        ['value' => 'sleeper',      'label' => 'Sleeper'],
    ];

    private const TIME_WINDOWS = [
        ['value' => 'morning',   'label' => 'Pagi (05.00–11.00)'],
        ['value' => 'afternoon', 'label' => 'Siang (11.00–15.00)'],
        ['value' => 'evening',   'label' => 'Sore (15.00–18.00)'],
        ['value' => 'night',     'label' => 'Malam (18.00–05.00)'],
    ];

    public function index(Request $request)
    {
        $originId = $request->input('origin');
        $originCity = is_scalar($originId) ? City::find($originId) : null;
        $timezone = $originCity?->timezone ?? env('APP_DISPLAY_TIMEZONE', 'Asia/Jakarta');
        $today = now($timezone)->toDateString();

        $data = $request->validate([
            'origin'      => ['required', 'exists:cities,id'],
            'destination' => ['required', 'exists:cities,id', 'different:origin'],
            'date'        => [
                'required',
                'date',
                function (string $attribute, mixed $value, Closure $fail) use ($today) {
                    if (Carbon::parse($value)->toDateString() < $today) {
                        $fail('Tanggal berangkat tidak boleh sebelum hari ini.');
                    }
                },
            ],
            'passengers'  => ['required', 'integer', 'between:1,6'],
        ], [
            'required'              => ':attribute wajib diisi.',
            'exists'                => ':attribute tidak valid.',
            'destination.different' => 'Kota tujuan harus berbeda dari kota asal.',
            'date.date'             => 'Tanggal berangkat tidak valid.',
            'passengers.between'    => 'Jumlah penumpang harus 1 sampai 6.',
        ], [
            'origin'      => 'Kota asal',
            'destination' => 'Kota tujuan',
            'date'        => 'Tanggal berangkat',
            'passengers'  => 'Jumlah penumpang',
        ]);

        // TODO (langkah berikutnya): query trips untuk rute + tanggal ini dan tampilkan daftar bus.
        return view('search.results', [
            'origin'      => City::findOrFail($data['origin']),
            'destination' => City::findOrFail($data['destination']),
            'date'        => $data['date'],
            'passengers'  => (int) $data['passengers'],
        ]);
    }
}
