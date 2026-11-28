<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Operator;
use App\Models\Route as RouteModel;
use App\Models\Trip;

class HomeController extends Controller
{
    public function index()
    {
        $cities = City::with('country')->orderBy('name')->get();

        $activeRoutes = RouteModel::query()
            ->where('is_active', true)
            ->with(['origin', 'destination'])
            ->get()
            ->filter(fn (RouteModel $route) => $route->origin && $route->destination)
            ->unique(fn (RouteModel $route) => $route->origin_city_id.'-'.$route->destination_city_id)
            ->values();

        $operators = Operator::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('home', [
            'cities'        => $cities,
            'popular'       => $cities->where('is_popular', true)->values(),
            'popularRoutes' => $activeRoutes->take(6)->values(),
            'operators'     => $operators,
            'stats'         => [
                'cities'    => $cities->count(),
                'operators' => $operators->count(),
                'routes'    => $activeRoutes->count(),
                'trips'     => Trip::query()->where('status', 'scheduled')->count(),
            ],
        ]);
    }
}
