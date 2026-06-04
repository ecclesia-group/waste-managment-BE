<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WasteHandoverRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'requester_provider_slug',
        'requester_type',
        'requester_name',
        'requester_phone',
        'requester_email',
        'submitted_by_slug',
        'target_provider_slug',
        'zone_slug',
        'zone_slugs',
        'title',
        'waste_types',
        'description',
        'pickup_location',
        'latitude',
        'longitude',
        'selected_driver_slug',
        'selected_fleet_slug',
        'images',
        'fee_amount',
        'payment_status',
        'paid_at',
        'status',
        'accepted_at',
        'completed_at',
    ];

    protected $casts = [
        'waste_types' => 'array',
        'zone_slugs' => 'array',
        'images' => 'array',
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

    /** Pending requests visible to a provider based on shared zone assignments. */
    public function scopeVisibleInProviderZones($query, array $providerZoneSlugs, ?string $excludeRequesterSlug = null)
    {
        $query->where('status', 'pending');

        if ($excludeRequesterSlug) {
            $query->where('requester_provider_slug', '!=', $excludeRequesterSlug);
        }

        if ($providerZoneSlugs === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(function ($q) use ($providerZoneSlugs) {
            $q->whereIn('zone_slug', $providerZoneSlugs);
            foreach ($providerZoneSlugs as $slug) {
                $q->orWhereJsonContains('zone_slugs', $slug);
            }
        });
    }
}
