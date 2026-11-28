<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('home') }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ route('ticket.lookup') }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
@foreach ($routes as $route)
    <url>
        <loc>{{ route('route.landing', ['origin' => $route->origin->slug, 'destination' => $route->destination->slug]) }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
@endforeach
</urlset>
