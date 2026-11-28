<?php

use App\Console\Commands\ReleaseExpiredBookings;
use Illuminate\Support\Facades\Schedule;

// Lepas kursi dari booking pending yang sudah melewati batas bayar.
// Jangan lupa mendaftarkan `php artisan schedule:run` ke cron/Task Scheduler.
Schedule::command(ReleaseExpiredBookings::class)->everyMinute();
