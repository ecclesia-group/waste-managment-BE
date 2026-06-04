<?php

namespace App\Traits;

use App\Models\RoutePlanner;
use App\Models\RoutePlannerBinAssignment;
use Illuminate\Support\Collection;

trait TransformsRoutePlannerResponse
{
    use HasClientMapPayload;

    protected static function assignmentIsScanned(RoutePlannerBinAssignment $assignment): bool
    {
        $assignmentScan = (string) ($assignment->scan_status ?? 'unscanned');

        return $assignmentScan === 'scanned'
            || ($assignment->pickup?->scan_status === 'scanned');
    }

    protected static function assignmentUiScanStatus(RoutePlannerBinAssignment $assignment): string
    {
        return static::assignmentIsScanned($assignment) ? 'scanned' : 'unscanned';
    }

    /**
     * Frontend shape: assignment (plan) with nested pickups (per client stop).
     */
    protected static function transformAssignment(RoutePlanner $plan): array
    {
        $plan->loadMissing([
            'provider',
            'driver',
            'fleet',
            'assignments.client.group',
            'assignments.pickup',
        ]);

        $pickups = [];
        $scanned = 0;
        $unscanned = 0;

        foreach ($plan->assignments as $row) {
            $client = $row->client;
            $pickup = $row->pickup;
            $coords = static::clientCoordinatesForMap($client);
            $scanStatus = static::assignmentUiScanStatus($row);

            if ($scanStatus === 'scanned') {
                $scanned++;
            } else {
                $unscanned++;
            }

            $pickups[] = [
                'pickup_code' => $row->pickup_code,
                'assignment_id' => $plan->id,
                'scan_status' => $scanStatus,
                'pickup_status' => $pickup?->status,
                'pickup_date' => $pickup?->pickup_date,
                'amount' => $pickup?->amount,
                'bulk_waste_request_code' => $pickup?->bulk_waste_request_code,
                'client' => [
                    'client_slug' => $row->client_slug,
                    'full_name' => trim(($client->first_name ?? '').' '.($client->last_name ?? '')),
                    'phone_number' => $client?->phone_number,
                    'email' => $client?->email,
                    'gps_address' => $client?->gps_address,
                    'pickup_location' => $client?->pickup_location,
                    'bin_code' => $client?->bin_code,
                    'group_slug' => $client?->group_slug,
                    'group_name' => $client?->group?->name,
                    'latitude' => $coords['latitude'],
                    'longitude' => $coords['longitude'],
                    'map_ready' => $coords['map_ready'],
                ],
            ];
        }

        $pickupType = $plan->pickup_type ?? ($plan->route_meta['pickup_type'] ?? 'normal');

        return [
            'assignment_id' => $plan->id,
            'id' => $plan->id,
            'pickup_type' => $pickupType,
            'type' => $pickupType,
            'pickup_date' => $plan->pickup_date?->toISOString(),
            'status' => $plan->status,
            'provider_slug' => $plan->provider_slug,
            'driver_slug' => $plan->driver_slug,
            'fleet_slug' => $plan->fleet_slug,
            'driver' => ($d = $plan->driver) ? static::transformRoutePlannerDriverBrief($d) : null,
            'fleet' => ($f = $plan->fleet) ? static::transformRoutePlannerFleetBrief($f) : null,
            'summary' => [
                'total' => count($pickups),
                'scanned' => $scanned,
                'unscanned' => $unscanned,
            ],
            'pickups' => $pickups,
            'created_at' => $plan->created_at,
            'updated_at' => $plan->updated_at,
        ];
    }

    protected static function transformAssignmentsList(Collection $plans): array
    {
        return $plans->map(fn (RoutePlanner $plan) => static::transformAssignment($plan))->values()->all();
    }

    protected static function transformRoutePlannerForFrontend(RoutePlanner $plan): array
    {
        return static::transformAssignment($plan);
    }

    protected static function transformRoutePlannerDriverBrief($driver): array
    {
        $full = trim(implode(' ', array_filter([
            $driver->first_name,
            $driver->middle_name ?? null,
            $driver->last_name,
        ])));

        $profile = $driver->profile_image ?? null;
        $profileUrl = is_array($profile) ? ($profile[0] ?? null) : $profile;

        return [
            'driver_slug' => $driver->driver_slug,
            'full_name' => $full,
            'phone_number' => $driver->phone_number,
            'email' => $driver->email,
            'display_label' => $full !== '' ? $full : $driver->driver_slug,
            'profile_image' => $profileUrl,
        ];
    }

    protected static function transformRoutePlannerFleetBrief($fleet): array
    {
        return [
            'fleet_slug' => $fleet->fleet_slug,
            'license_plate' => $fleet->license_plate,
            'vehicle_make' => $fleet->vehicle_make,
            'model' => $fleet->model,
            'display_label' => $fleet->license_plate ? (string) $fleet->license_plate : $fleet->fleet_slug,
        ];
    }
}
