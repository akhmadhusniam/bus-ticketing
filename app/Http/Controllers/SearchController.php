<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'origin'      => ['required', 'exists:cities,id'],
            'destination' => ['required', 'exists:cities,id', 'different:origin'],
            'date'        => ['required', 'date', 'after_or_equal:today'],
            'passengers'  => ['required', 'integer', 'between:1,6'],
        ], [
            'required'               => ':attribute wajib diisi.',
            'exists'                 => ':attribute tidak valid.',
            'destination.different'  => 'Kota tujuan harus berbeda dari kota asal.',
            'date.after_or_equal'    => 'Tanggal berangkat tidak boleh sebelum hari ini.',
            'passengers.between'     => 'Jumlah penumpang harus 1 sampai 6.',
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
