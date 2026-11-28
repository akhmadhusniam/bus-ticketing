<?php

namespace App\Http\Controllers;

use App\Models\Route as RouteModel;

class SitemapController extends Controller
{
    public function index()
    {
        $routes = RouteModel::query()
            ->where('is_active', true)
            ->with(['origin', 'destination'])
            ->get()
            ->filter(fn (RouteModel $route) => $route->origin && $route->destination)
            ->unique(fn (RouteModel $route) => $route->origin_city_id.'-'.$route->destination_city_id)
            ->values();

        $xml = view('sitemap', ['routes' => $routes])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=utf-8');
    }
}
