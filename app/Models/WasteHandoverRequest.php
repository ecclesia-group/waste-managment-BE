<?php

namespace App\Models;

use App\Traits\ScopesProviderOrganisation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class WasteHandoverRequest extends Model
{
    use ScopesProviderOrganisation, SoftDeletes;

    protected $fillable = [
        'code',
        'requester_provider_slug',
        'target_provider_slug',
        'pickup_location',
        'gps_address',
        'latitude',
        'longitude',
        'fleet_type',
        'selected_driver_slug',
        'selected_fleet_slug',
        'fee_amount',
        'payment_status',
        'paid_at',
        'status',
        'accepted_at',
        'completed_at',
    ];

    protected $casts = [
        'fee_amount' => 'float',
        'latitude' => 'float',
        'longitude' => 'float',
        'accepted_at' => 'datetime',
        'completed_at' => 'datetime',
        'paid_at' => 'datetime',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(Provider::class, 'requester_provider_slug', 'provider_slug');
    }

    public function acceptedProvider()
    {
        return $this->belongsTo(Provider::class, 'target_provider_slug', 'provider_slug');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'selected_driver_slug', 'driver_slug');
    }

    public function fleet()
    {
        return $this->belongsTo(Fleet::class, 'selected_fleet_slug', 'fleet_slug');
    }

    public function declines()
    {
        return $this->hasMany(HandoverDecline::class, 'waste_handover_request_id');
    }

    public function scopeInProviderZones(Builder $query, array $zoneIds): Builder
    {
        if ($zoneIds === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereExists(function ($sub) use ($zoneIds) {
            $sub->select(DB::raw(1))
                ->from('provider_zones')
                ->whereColumn('provider_zones.provider_slug', 'waste_handover_requests.requester_provider_slug')
                ->where('provider_zones.status', 'active')
                ->whereIn('provider_zones.zone_id', $zoneIds);
        });
    }

    /** Pending requests visible to zone peers (excludes own requests + declined). */
    public function scopeVisibleInProviderZones(
        Builder $query,
        array $providerZoneIds,
        string $excludeRequesterSlug,
        ?string $viewingProviderSlug = null
    ): Builder {
        return $query
            ->where('status', 'pending')
            ->where('requester_provider_slug', '!=', $excludeRequesterSlug)
            ->when($viewingProviderSlug, fn ($q) => $q->whereDoesntHave(
                'declines',
                fn ($decline) => $decline->where('provider_slug', $viewingProviderSlug)
            ))
            ->inProviderZones($providerZoneIds);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
