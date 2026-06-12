<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class WasteHandoverRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'submitted_by_slug',
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

    /** Actor (team member) who created the request. */
    public function submittedBy()
    {
        return $this->belongsTo(Provider::class, 'submitted_by_slug', 'provider_slug');
    }

    /** Main provider organisation that owns the request. */
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

    public function scopeInProviderZones(Builder $query, array $zoneSlugs): Builder
    {
        if ($zoneSlugs === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereExists(function ($sub) use ($zoneSlugs) {
            $sub->select(DB::raw(1))
                ->from('provider_zones')
                ->whereColumn('provider_zones.provider_slug', 'waste_handover_requests.requester_provider_slug')
                ->where('provider_zones.status', 'active')
                ->whereIn('provider_zones.zone_slug', $zoneSlugs);
        });
    }

    /** Pending requests visible to zone peers (excludes own organisation + declined). */
    public function scopeVisibleInProviderZones(
        Builder $query,
        array $providerZoneSlugs,
        string $excludeOwnerSlug,
        ?string $viewingProviderSlug = null
    ): Builder {
        return $query
            ->where('status', 'pending')
            ->where('requester_provider_slug', '!=', $excludeOwnerSlug)
            ->when($viewingProviderSlug, fn ($q) => $q->whereDoesntHave(
                'declines',
                fn ($decline) => $decline->where('provider_slug', $viewingProviderSlug)
            ))
            ->inProviderZones($providerZoneSlugs);
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
