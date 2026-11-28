<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RouteLandingController;
use App\Http\Controllers\SearchResultsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TicketLookupController;
use App\Http\Controllers\TripDetailController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/cari', [SearchResultsController::class, 'index'])->name('search');
Route::get('/trip/{trip}', [TripDetailController::class, 'show'])->name('trip.detail');

Route::get('/booking/{trip}', [BookingController::class, 'show'])->name('booking.show');
Route::post('/booking/{trip}', [BookingController::class, 'store'])->name('booking.store');

Route::get('/pembayaran/{code}', [PaymentController::class, 'show'])->name('payment.show');
Route::post('/pembayaran/{code}', [PaymentController::class, 'pay'])->name('payment.pay');

Route::get('/cek-tiket', [TicketLookupController::class, 'show'])->name('ticket.lookup');
Route::post('/cek-tiket', [TicketLookupController::class, 'find'])->name('ticket.find');
Route::get('/tiket/{code}', [TicketLookupController::class, 'receipt'])->name('ticket.show');

Route::get('/rute/{origin}/{destination}', [RouteLandingController::class, 'show'])
    ->where(['origin' => '[a-z0-9-]+', 'destination' => '[a-z0-9-]+'])
    ->name('route.landing');

// Alias ringkas untuk slug satu kata: /rute/jakarta-bandung
Route::get('/rute/{origin}-{destination}', [RouteLandingController::class, 'show'])
    ->where(['origin' => '[a-z0-9]+', 'destination' => '[a-z0-9]+'])
    ->name('route.landing.hyphen');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
