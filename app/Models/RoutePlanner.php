<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoutePlanner extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'provider_slug',
        'driver_slug',
        'fleet_slug',
        'group_slug',
        'status',
        'route_meta',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'route_meta' => 'array',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_slug', 'provider_slug');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_slug', 'driver_slug');
    }

    public function fleet()
    {
        return $this->belongsTo(Fleet::class, 'fleet_slug', 'fleet_slug');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_slug', 'group_slug');
    }

    /**
     * Active clients in this route’s group (same provider + group as the plan).
     */
    public function clients()
    {
        return $this->hasMany(Client::class, 'group_slug', 'group_slug')
            ->where('clients.provider_slug', $this->provider_slug);
    }

    /**
     * Bin assignment rows for this route (one per client pickup in the plan).
     */
    public function assignments()
    {
        return $this->hasMany(RoutePlannerBinAssignment::class, 'route_planner_id');
    }
}
