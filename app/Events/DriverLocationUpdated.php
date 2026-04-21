<?php

namespace App\Events;

use App\Models\Driver;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a driver's live coordinates are persisted. Wire to broadcasting in EventServiceProvider when needed.
 */
class DriverLocationUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Driver $driver,
        public float $latitude,
        public float $longitude,
    ) {}
}
