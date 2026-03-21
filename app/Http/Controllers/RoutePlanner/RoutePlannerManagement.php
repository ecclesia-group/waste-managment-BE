<?php
namespace App\Http\Controllers\RoutePlanner;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoutePlanner\RegisterRoute;
use App\Http\Requests\RoutePlanner\RouteDetailsUpdate;
use App\Http\Requests\RoutePlanner\RouteStatusUpdate;
use App\Models\Client;
use App\Models\Driver;
use App\Models\Fleet;
use App\Models\Provider;
use App\Models\Pickup;
use App\Models\RoutePlanner;
use App\Models\RoutePlannerBinAssignment;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoutePlannerManagement extends Controller
{
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
            $query->where('provider_slug', $user->provider_slug);
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
            $data['provider_slug'] = $user->provider_slug;
        }

        DB::beginTransaction();
        try {
            // Ensure referenced driver/fleet/group belong to the authenticated provider.
            if (isset($user->provider_slug)) {
                $driver = Driver::query()
                    ->where('driver_slug', $data['driver_slug'])
                    ->where('provider_slug', $user->provider_slug)
                    ->first();
                if (! $driver) {
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
                    ->where('provider_slug', $user->provider_slug)
                    ->first();
                if (! $fleet) {
                    return self::apiResponse(
                        in_error: true,
                        message: "Action Failed",
                        reason: "Unauthorized fleet for this provider",
                        status_code: self::API_FAIL,
                        data: []
                    );
                }

                $group = Group::query()
                    ->where('group_slug', $data['group_slug'])
                    ->where('provider_slug', $user->provider_slug)
                    ->first();
                if (! $group) {
                    return self::apiResponse(
                        in_error: true,
                        message: "Action Failed",
                        reason: "Unauthorized group for this provider",
                        status_code: self::API_FAIL,
                        data: []
                    );
                }
            }

            $routePlanner = RoutePlanner::create($data);

            // Create a pending pickup + assignment row for every active client in this group.
            $clients = Client::query()
                ->where('provider_slug', $routePlanner->provider_slug)
                ->where('group_id', $routePlanner->group_slug)
                ->where('status', 'active')
                ->get();

            if ($clients->isEmpty()) {
                DB::rollBack();
                return self::apiResponse(
                    in_error: true,
                    message: "Action Failed",
                    reason: "No active clients found for this group",
                    status_code: self::API_NOT_FOUND,
                    data: []
                );
            }

            foreach ($clients as $client) {
                $pickupCode = self::generateUniquePickupCode();

                $location = $client->pickup_location ?: ($client->gps_address ?: 'Unknown');

                $pickup = Pickup::create([
                    'code' => $pickupCode,
                    'client_slug' => $client->client_slug,
                    'title' => 'Scheduled Pickup',
                    'category' => 'Household',
                    'description' => null,
                    'amount' => null,
                    'status' => 'pending',
                    'scan_status' => 'pending',
                    'location' => $location,
                    'provider_slug' => $routePlanner->provider_slug,
                    'images' => null,
                    'pickup_date' => null,
                ]);

                RoutePlannerBinAssignment::create([
                    'route_planner_id' => $routePlanner->id,
                    'provider_slug' => $routePlanner->provider_slug,
                    'driver_slug' => $routePlanner->driver_slug,
                    'fleet_slug' => $routePlanner->fleet_slug,
                    'group_slug' => $routePlanner->group_slug,
                    'client_slug' => $client->client_slug,
                    'pickup_code' => $pickup->code,
                    'scan_status' => 'pending',
                ]);
            }

            DB::commit();

            $routePlanner->load(['driver', 'fleet', 'group']);

            return self::apiResponse(
                in_error: false,
                message: "Action Successful",
                reason: "Route created successfully",
                status_code: self::API_SUCCESS,
                data: $routePlanner->toArray()
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
            'client', // provider
            'driver',
            'fleet',
            'group',
        ])
            ->where('provider_slug', $user->provider_slug)
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
        if (isset($user->provider_slug) && $plan->provider_slug !== $user->provider_slug) {
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
            'client',
            'driver',
            'fleet',
            'group',
        ]);

        // Provide bins with scan statuses for the map UI.
        $assignments = RoutePlannerBinAssignment::query()
            ->where('route_planner_id', $plan->id)
            ->with([
                'client',
                'pickup',
            ])
            ->get();

        $bins = $assignments->map(function (RoutePlannerBinAssignment $assignment) {
            // Frontend expects `scanned` vs `unscanned` colors.
            $uiStatus = match ($assignment->scan_status) {
                'scanned' => 'scanned',
                'pending', 'not_scanned' => 'unscanned',
                default => 'unscanned',
            };

            return [
                'pickup_code' => $assignment->pickup_code,
                'scan_status' => $uiStatus,
                'scanned_at' => $assignment->scanned_at?->toISOString(),
                'unscanned_at' => $assignment->unscanned_at?->toISOString(),
                'client' => $assignment->client?->toArray(),
                'pickup' => $assignment->pickup?->toArray(),
            ];
        })->toArray();

        $payload = $plan->toArray();
        $payload['bins'] = $bins;

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
                $q->where('provider_slug', $user->provider_slug);
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
        if (isset($user->provider_slug) && (string) $plan->provider_slug !== (string) $user->provider_slug) {
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
                    ->where('provider_slug', $user->provider_slug)
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
                    ->where('provider_slug', $user->provider_slug)
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
                    ->where('provider_slug', $user->provider_slug)
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
                    ->where('provider_slug', $user->provider_slug)
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
        if (isset($user->provider_slug) && (string) $plan->provider_slug !== (string) $user->provider_slug) {
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
}
