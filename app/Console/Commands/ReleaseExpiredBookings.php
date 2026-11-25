<?php

namespace App\Console\Commands;

use App\Services\Booking\SeatLockService;
use Illuminate\Console\Command;

class ReleaseExpiredBookings extends Command
{
    protected $signature = 'bookings:release-expired';
    protected $description = 'Lepas kursi dari booking yang belum dibayar dan sudah kedaluwarsa';

    public function handle(SeatLockService $service): int
    {
        $this->info("Dilepas: {$service->releaseExpired()} booking");
        return self::SUCCESS;
    }
}
