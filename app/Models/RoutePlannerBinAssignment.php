<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoutePlannerBinAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'route_planner_id',
        'provider_slug',
        'driver_slug',
        'fleet_slug',
        'group_slug',
        'client_slug',
        'pickup_code',
        'scan_status',
        'scanned_at',
        'unscanned_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'unscanned_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function routePlanner()
    {
        return $this->belongsTo(RoutePlanner::class, 'route_planner_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_slug', 'client_slug');
    }

    public function pickup()
    {
        return $this->belongsTo(Pickup::class, 'pickup_code', 'code');
    }
}

