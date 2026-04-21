<?php
namespace App\Http\Controllers\RoutePlanner;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoutePlanner\RegisterRoute;
use App\Http\Requests\RoutePlanner\RouteDetailsUpdate;
use App\Http\Requests\RoutePlanner\RouteStatusUpdate;
use App\Models\BulkWasteRequest;
use App\Models\Client;
use App\Models\Driver;
use App\Models\Fleet;
use App\Models\Provider;
use App\Models\Pickup;
use App\Models\RoutePlanner;
use App\Models\RoutePlannerBinAssignment;
use App\Models\Group;
use App\Services\ProviderZoneValidationService;
use App\Services\RouteOptimizationService;
use App\Traits\TransformsRoutePlannerResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoutePlannerManagement extends Controller
{
    use TransformsRoutePlannerResponse;

    // Provider (and admin/super-admin) can use this to power assignment logs filtering.
    public function assignmentLogs(Request $request)
    {
        $user = $request->user();

        $query = RoutePlannerBinAssignment::query()
            ->with([
                'client',
                'pickup',
                'routePlanner',
            ]);

        // Provider sees only their own data by default.
        if (isset($user->provider_slug)) {
            $query->where('provider_slug', $this->resolveProviderScopeSlug($user));
        }

        if (isset($user->driver_slug)) {
            $query->where('driver_slug', $user->driver_slug);
        }

        // District Assembly (MMDA) should see only providers within their jurisdiction.
        if (isset($user->district_assembly_slug)) {
            $query->whereIn('provider_slug', function ($q) use ($user) {
                $q->select('provider_slug')
                    ->from('providers')
                    ->where('district_assembly', $user->district_assembly_slug);
            });
        }

        if ($request->filled('provider_slug')) {
            $query->where('provider_slug', $request->string('provider_slug'));
        }
        if ($request->filled('driver_slug')) {
            $query->where('driver_slug', $request->string('driver_slug'));
        }
        if ($request->filled('group_slug')) {
            $query->where('group_slug', $request->string('group_slug'));
        }

        // status: scanned | unscanned | pending
        if ($request->filled('status')) {
            $status = $request->string('status');
            if ($status === 'scanned') {
                $query->where('scan_status', 'scanned');
            } elseif ($status === 'unscanned') {
                $query->whereIn('scan_status', ['pending', 'not_scanned']);
            } elseif ($status === 'pending') {
                $query->where('scan_status', 'pending');
            }
        }

        // Apply date filters to a relevant event timestamp.
        if ($request->filled('from') || $request->filled('to')) {
            $from = $request->filled('from') ? $request->date('from') : null;
            $to = $request->filled('to') ? $request->date('to') : null;
            $status = $request->filled('status') ? $request->string('status') : null;

            $timestampColumn = match ($status) {
                'scanned' => 'scanned_at',
                'unscanned' => 'unscanned_at',
                default => 'created_at',
            };

            if ($from) {
                $query->whereDate($timestampColumn, '>=', $from);
            }
            if ($to) {
                $query->whereDate($timestampColumn, '<=', $to);
            }
        }

        $perPage = $request->integer('limit', 20);
        $perPage = max(1, min(100, $perPage));

        $logs = $query->latest()->paginate($perPage);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Assignment logs retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $logs->toArray()
        );
    }

    public function register(RegisterRoute $request)
    {
        $user = $request->user();
        $data = $request->validated();

        // Tenant isolation: provider can only create plans under their own provider_slug.
        // Ignore any `provider_slug` from the request payload.
        if (isset($user->provider_slug)) {
            $data['provider_slug'] = $this->resolveProviderScopeSlug($user);
        }

        DB::beginTransaction();
        try {
            // Ensure referenced driver/fleet/group belong to the authenticated provider.
            if (isset($user->provider_slug)) {
                $driver = Driver::query()
                    ->where('driver_slug', $data['driver_slug'])
                    ->where('provider_slug', $data['provider_slug'])
                    ->first();
                if (! $driver) {
                    DB::rollBack();

                    return self::apiResponse(
                        in_error: true,
                        message: "Action Failed",
                        reason: "Unauthorized driver for this provider",
                        status_code: self::API_FAIL,
                        data: []
                    );
                }

                $fleet = Fleet::query()
                    ->where('fleet_slug', $data['fleet_slug'])
                    ->where('provider_slug', $data['provider_slug'])
                    ->first();
                if (! $fleet) {
                    DB::rollBack();

                    return self::apiResponse(
                        in_error: true,
                        message: "Action Failed",
                        reason: "Unauthorized fleet for this provider",
                        status_code: self::API_FAIL,
                        data: []
                    );
                }

                if (! empty($data['group_slug'])) {
                    $group = Group::query()
                        ->where('group_slug', $data['group_slug'])
                        ->where('provider_slug', $data['provider_slug'])
                        ->first();
                    if (! $group) {
                        DB::rollBack();
                        return self::apiResponse(
                            in_error: true,
                            message: "Action Failed",
                            reason: "Unauthorized group for this provider",
                            status_code: self::API_FAIL,
                            data: []
                        );
                    }
                }
            }

            $routePlanner = RoutePlanner::create($data);

            $providerModel = Provider::query()
                ->where('provider_slug', $routePlanner->provider_slug)
                ->with(['zones' => fn ($q) => $q->wherePivot('status', 'active')])
                ->first();

            $assignedZones = $providerModel?->zones ?? collect();
            $zoneValidation = app(ProviderZoneValidationService::class);

            $clients = Client::query()
                ->where('provider_slug', $routePlanner->provider_slug)
                ->where('status', 'active')
                ->when($clientSlugs->isNotEmpty(), function ($query) use ($clientSlugs) {
                    $query->whereIn('client_slug', $clientSlugs->all());
                }, function ($query) use ($routePlanner) {
                    $query->where('group_slug', $routePlanner->group_slug);
                })
                ->get();

            $eligibleClients = $clients->filter(
                fn (Client $c) => $zoneValidation->clientIsWithinAssignedZones($c, $assignedZones)
            )->values();

            if ($eligibleClients->isEmpty()) {
                DB::rollBack();

                return self::apiResponse(
                    in_error: true,
                    message: "Action Failed",
                    reason: 'No active clients with valid coordinates inside assigned zones for this group',
                    status_code: self::API_NOT_FOUND,
                    data: []
                );
            }

            $driverModel = Driver::query()
                ->where('driver_slug', $routePlanner->driver_slug)
                ->where('provider_slug', $routePlanner->provider_slug)
                ->first();

            $driverLocation = ($driverModel
                && $driverModel->latitude !== null
                && $driverModel->longitude !== null) ? [
                    'latitude' => (float) $driverModel->latitude,
                    'longitude' => (float) $driverModel->longitude,
                ] : null;

            $stops = [];

            foreach ($eligibleClients as $client) {
                $pickupCode = self::generateUniquePickupCode();

                $location = $client->pickup_location ?: ($client->gps_address ?: 'Unknown');

                Pickup::create([
                    'code' => $pickupCode,
                    'bulk_waste_request_code' => $approvedBulkRequest->request_code,
                    'client_slug' => $client->client_slug,
                    'title' => $approvedBulkRequest->title,
                    'category' => $approvedBulkRequest->category,
                    'description' => $approvedBulkRequest->description,
                    'amount' => null,
                    'status' => 'pending',
                    'scan_status' => 'pending',
                    'location' => $location,
                    'provider_slug' => $routePlanner->provider_slug,
                    'images' => $approvedBulkRequest->images,
                    'pickup_date' => now(),
                ]);

                $approvedBulkRequest->pickup_date = $pickup->pickup_date;
                $approvedBulkRequest->status = 'scheduled';
                $approvedBulkRequest->save();

                RoutePlannerBinAssignment::create([
                    'route_planner_id' => $routePlanner->id,
                    'provider_slug' => $routePlanner->provider_slug,
                    'driver_slug' => $routePlanner->driver_slug,
                    'fleet_slug' => $routePlanner->fleet_slug,
                    'group_slug' => $routePlanner->group_slug,
                    'client_slug' => $client->client_slug,
                    'pickup_code' => $pickupCode,
                    'scan_status' => 'pending',
                ]);

                $stops[] = [
                    'key' => $pickupCode,
                    'latitude' => (float) $client->latitude,
                    'longitude' => (float) $client->longitude,
                ];
            }

            $optimizer = app(RouteOptimizationService::class);
            $optimized = $optimizer->optimizeRoute($driverLocation, $stops);

            $routePlanner->route_meta = [
                'optimization' => [
                    'source' => $optimized['source'],
                    'total_distance_meters' => $optimized['total_distance_meters'],
                    'total_duration_seconds' => $optimized['total_duration_seconds'],
                    'encoded_polyline' => $optimized['encoded_polyline'],
                    'ordered_pickup_codes' => $optimized['ordered_keys'],
                ],
                'excluded_clients_count' => $clients->count() - $eligibleClients->count(),
            ];
            $routePlanner->save();

            foreach ($optimized['ordered_keys'] as $index => $pickupCode) {
                RoutePlannerBinAssignment::query()
                    ->where('route_planner_id', $routePlanner->id)
                    ->where('pickup_code', $pickupCode)
                    ->update([
                        'stop_order' => $index + 1,
                        'eta_minutes' => $optimized['leg_eta_minutes'][$index] ?? null,
                    ]);
            }

            DB::commit();

            $routePlanner->refresh();
            $routePlanner->load(['assignments.client.group', 'assignments.pickup', 'provider', 'driver', 'fleet', 'group']);

            return self::apiResponse(
                in_error: false,
                message: "Action Successful",
                reason: "Route created successfully",
                status_code: self::API_SUCCESS,
                data: self::transformRoutePlannerForFrontend($routePlanner)
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Failed to create route: " . $e->getMessage(),
                status_code: self::API_FAIL,
                data: []
            );
        }
    }

    public function allPlans()
    {
        $user = Auth::user();

        $routePlanner = RoutePlanner::with([
            'provider',
            'driver',
            'fleet',
            'group',
        ])
            ->where('provider_slug', $this->resolveProviderScopeSlug($user))
            ->get();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Routes retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $routePlanner->toArray()
        );
    }

    public function show(RoutePlanner $plan)
    {
        $user = request()->user();
        if (isset($user->provider_slug) && $plan->provider_slug !== $this->resolveProviderScopeSlug($user)) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to view this plan",
                status_code: self::API_FAIL,
                data: []
            );
        }

        if (isset($user->district_assembly_slug)) {
            $allowed = Provider::query()
                ->where('provider_slug', $plan->provider_slug)
                ->where('district_assembly', $user->district_assembly_slug)
                ->exists();

            if (! $allowed) {
                return self::apiResponse(
                    in_error: true,
                    message: "Action Failed",
                    reason: "Plan not found in your jurisdiction",
                    status_code: self::API_NOT_FOUND,
                    data: []
                );
            }
        }

        // Load relations
        $plan->load([
            'provider',
            'driver',
            'fleet',
            'group',
        ]);

        // Provide bins with scan statuses for the map UI.
        $assignments = RoutePlannerBinAssignment::query()
            ->where('route_planner_id', $plan->id)
            ->with([
                'client.group',
                'pickup',
            ])
            ->get();

        $scanned = 0;
        $unscanned = 0;

        $bins = $assignments->map(function (RoutePlannerBinAssignment $assignment) use (&$scanned, &$unscanned) {
            $pickup = $assignment->pickup;
            $assignmentScan = $assignment->scan_status ?? 'pending';
            $effective = $assignmentScan === 'scanned' || ($pickup?->scan_status === 'scanned');
            if ($effective) {
                $scanned++;
            } else {
                $unscanned++;
            }

            // Frontend expects `scanned` vs `unscanned` colors.
            $uiStatus = match ($assignmentScan) {
                'scanned' => 'scanned',
                'pending', 'not_scanned' => 'unscanned',
                default => 'unscanned',
            };

            $client = $assignment->client;
            $coords = static::clientCoordinatesForMap($client);

            return [
                'pickup_code' => $assignment->pickup_code,
                'client_slug' => $assignment->client_slug,
                'scan_status' => $uiStatus,
                'map_marker_color' => $uiStatus === 'scanned' ? 'green' : 'red',
                'is_scanned' => $effective,
                'scanned_at' => $assignment->scanned_at?->toISOString(),
                'unscanned_at' => $assignment->unscanned_at?->toISOString(),
                'coordinates' => $coords,
                'client' => $client ? array_merge($client->toArray(), [
                    'coordinates' => $coords,
                ]) : null,
                'pickup' => $pickup?->toArray(),
            ];
        })->toArray();

        $payload = $plan->toArray();
        $payload['bins'] = $bins;
        $payload['map_summary'] = [
            'scanned' => $scanned,
            'unscanned' => $unscanned,
            'total' => $assignments->count(),
        ];

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Route details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $payload
        );
    }

    public function updateStatus(RouteStatusUpdate $request)
    {
        $data                 = $request->validated();

        $user = $request->user();
        $routePlanner = RoutePlanner::query()
            ->where('id', $data['id'])
            ->when(isset($user->provider_slug), function ($q) use ($user) {
                $q->where('provider_slug', $this->resolveProviderScopeSlug($user));
            })
            ->first();

        if (! $routePlanner) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Route plan not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }
        $routePlanner->status = $data['status'];
        $routePlanner->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Route planner status updated successfully",
            status_code: self::API_SUCCESS,
            data: $routePlanner->toArray()
        );
    }

    public function updatePlan(RouteDetailsUpdate $request, RoutePlanner $plan)
    {
        $data = $request->validated();

        $user = $request->user();
        if (isset($user->provider_slug) && (string) $plan->provider_slug !== (string) $this->resolveProviderScopeSlug($user)) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to update this plan",
                status_code: self::API_FAIL,
                data: []
            );
        }

        // Tenant isolation for mutable foreign keys.
        if (isset($user->provider_slug)) {
            if (isset($data['driver_slug'])) {
                $allowed = Driver::query()
                    ->where('driver_slug', $data['driver_slug'])
                    ->where('provider_slug', $this->resolveProviderScopeSlug($user))
                    ->exists();

                if (! $allowed) {
                    return self::apiResponse(
                        in_error: true,
                        message: "Action Failed",
                        reason: "Unauthorized driver for this provider",
                        status_code: self::API_FAIL,
                        data: []
                    );
                }
            }

            if (isset($data['fleet_slug'])) {
                $allowed = Fleet::query()
                    ->where('fleet_slug', $data['fleet_slug'])
                    ->where('provider_slug', $this->resolveProviderScopeSlug($user))
                    ->exists();

                if (! $allowed) {
                    return self::apiResponse(
                        in_error: true,
                        message: "Action Failed",
                        reason: "Unauthorized fleet for this provider",
                        status_code: self::API_FAIL,
                        data: []
                    );
                }
            }

            if (isset($data['group_slug'])) {
                $allowed = Group::query()
                    ->where('group_slug', $data['group_slug'])
                    ->where('provider_slug', $this->resolveProviderScopeSlug($user))
                    ->exists();

                if (! $allowed) {
                    return self::apiResponse(
                        in_error: true,
                        message: "Action Failed",
                        reason: "Unauthorized group for this provider",
                        status_code: self::API_FAIL,
                        data: []
                    );
                }
            }

            if (isset($data['client_slug'])) {
                $allowed = Client::query()
                    ->where('client_slug', $data['client_slug'])
                    ->where('provider_slug', $this->resolveProviderScopeSlug($user))
                    ->exists();

                if (! $allowed) {
                    return self::apiResponse(
                        in_error: true,
                        message: "Action Failed",
                        reason: "Unauthorized client for this provider",
                        status_code: self::API_FAIL,
                        data: []
                    );
                }
            }
        }

        $plan->update($data);
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Route details updated successfully",
            status_code: self::API_SUCCESS,
            data: $plan->toArray()
        );
    }

    public function deletePlan(RoutePlanner $plan)
    {
        $user = request()->user();
        if (isset($user->provider_slug) && (string) $plan->provider_slug !== (string) $this->resolveProviderScopeSlug($user)) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to delete this plan",
                status_code: self::API_FAIL,
                data: []
            );
        }

        if (! $plan) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Route plan not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        $plan->delete();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Route deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }

    protected static function generateUniquePickupCode(): string
    {
        // Ensure uniqueness under concurrent route-plan creation.
        do {
            $code = Str::upper(Str::random(8));
        } while (Pickup::where('code', $code)->exists());

        return $code;
    }

    private function resolveProviderScopeSlug(object $user): ?string
    {
        if (! isset($user->provider_slug)) {
            return null;
        }

        return (bool) ($user->is_main ?? true)
            ? (string) $user->provider_slug
            : (string) ($user->parent_slug ?: $user->provider_slug);
    }
}
