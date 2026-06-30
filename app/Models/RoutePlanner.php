<?php
namespace App\Models;

use App\Traits\ScopesProviderOrganisation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoutePlanner extends Model
{
    use ScopesProviderOrganisation, SoftDeletes;

    protected $fillable = [
        'provider_slug',
        'driver_slug',
        'fleet_slug',
        'group_slug',
        'status',
        'pickup_date',
        'pickup_type',
        'route_meta',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'route_meta' => 'array',
        'pickup_date' => 'datetime',
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

    public function pickups()
    {
        return $this->hasMany(Pickup::class, 'route_planner_id');
    }

    /** @return list<string> */
    public function selectedGroupSlugs(): array
    {
        if (($this->pickup_type ?? null) !== 'normal') {
            return [];
        }

        $fromMeta = $this->route_meta['selected_group_slugs'] ?? null;

        if (is_array($fromMeta) && $fromMeta !== []) {
            return array_values($fromMeta);
        }

        return $this->group_slug ? [$this->group_slug] : [];
    }

    /** @return list<string> */
    public function selectedBulkRequestCodes(): array
    {
        if (($this->pickup_type ?? null) !== 'bulk_waste_request') {
            return [];
        }

        $fromMeta = $this->route_meta['selected_bulk_request_codes'] ?? null;

        return is_array($fromMeta) ? array_values($fromMeta) : [];
    }
}
