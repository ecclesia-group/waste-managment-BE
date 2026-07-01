<?php

namespace App\Traits;

use App\Models\Provider;
use App\Models\Zone;
use App\Services\ZoneAssignmentService;
use Illuminate\Http\Request;

trait RespondsWithZoneAssignments
{
    use ApiTransformer;

    protected function validateZoneAssignmentRequest(Request $request): array
    {
        return $request->validate([
            'zone_ids' => ['required', 'array', 'min:1'],
            'zone_ids.*' => ['required', 'integer', 'distinct', 'exists:zones,id'],
            'replace' => ['sometimes', 'boolean'],
        ]);
    }

    protected function listProviderZonesResponse(Provider $provider)
    {
        $assignments = app(ZoneAssignmentService::class)
            ->listProviderZoneAssignments($provider->provider_slug);

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Provider zones retrieved successfully',
            status_code: self::API_SUCCESS,
            data: $assignments->toArray()
        );
    }

    protected function assignProviderZonesResponse(Request $request, Provider $provider)
    {
        $data = $this->validateZoneAssignmentRequest($request);

        app(ZoneAssignmentService::class)->setProviderZones(
            $provider->provider_slug,
            $data['zone_ids'],
            (bool) ($data['replace'] ?? false)
        );

        $assignments = app(ZoneAssignmentService::class)
            ->listActiveProviderZoneAssignments($provider->provider_slug);

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Provider zones assigned successfully',
            status_code: self::API_SUCCESS,
            data: $assignments->toArray()
        );
    }

    protected function revokeProviderZoneResponse(Provider $provider, Zone $zone)
    {
        $revoked = app(ZoneAssignmentService::class)
            ->revokeProviderZone($provider->provider_slug, (int) $zone->id);

        if (! $revoked) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: 'Provider zone assignment not found',
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Provider zone revoked successfully',
            status_code: self::API_SUCCESS,
            data: []
        );
    }
}
