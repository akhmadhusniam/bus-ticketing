<?php

namespace App\Http\Controllers;

use App\Models\City;

class HomeController extends Controller
{
    public function index()
    {
        $cities = City::with('country')->orderBy('name')->get();

        return view('home', [
            'cities'  => $cities,
            'popular' => $cities->where('is_popular', true)->values(),
        ]);
    }
}
