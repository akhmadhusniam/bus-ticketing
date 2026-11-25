<?php

namespace App\Exceptions;

use RuntimeException;

class SeatUnavailableException extends RuntimeException
{
    public function __construct(public readonly array $seatLabels)
    {
        parent::__construct('Kursi sudah tidak tersedia: ' . implode(', ', $seatLabels));
    }
}
