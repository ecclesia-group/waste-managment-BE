<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickupScanEvent extends Model
{
    protected $fillable = [
        'provider_slug',
        'pickup_code',
        'idempotency_key',
        'device_scanned_at',
    ];

    protected $casts = [
        'device_scanned_at' => 'datetime',
    ];
}
