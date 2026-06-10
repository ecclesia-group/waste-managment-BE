<?php
namespace App\Http\Controllers\RoutePlanner;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoutePlanner\RegisterRoute;
use App\Http\Requests\RoutePlanner\RouteDetailsUpdate;
use App\Http\Requests\RoutePlanner\RouteStatusUpdate;
use App\Models\Driver;
use App\Models\Fleet;
use App\Models\Pickup;
use App\Models\Provider;
use App\Models\RoutePlanner;
use App\Services\RoutePlannerService;
use App\Traits\TransformsRoutePlannerResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use RuntimeException;

class RoutePlannerManagement extends Controller
{
    use TransformsRoutePlannerResponse;

    public function __construct(
        private readonly RoutePlannerService $routePlannerService
    ) {}

    public function planOptions(Request $request)
    {
        $providerSlug = $this->resolveProviderScopeSlug($request->user());

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Route planner options retrieved successfully',
            status_code: self::API_SUCCESS,
            data: $this->routePlannerService->planOptionsForProvider($providerSlug)
        );
    }

    public function assignmentLogs(Request $request)
    {
        $user = $request->user();

        // Pickups tied to a route plan are the assignment "logs" (one stop per client).
        $query = Pickup::query()
            ->whereNotNull('route_planner_id')
            ->with(['client', 'routePlanner']);

        if (isset($user->provider_slug)) {
            $query->where('provider_slug', $this->resolveProviderScopeSlug($user));
        }

        if (isset($user->driver_slug)) {
            $query->whereHas('routePlanner', fn ($q) => $q->where('driver_slug', $user->driver_slug));
        }

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
            $query->whereHas('routePlanner', fn ($q) => $q->where('driver_slug', $request->string('driver_slug')));
        }
        if ($request->filled('group_slug')) {
            $query->where('group_slug', $request->string('group_slug'));
        }
        if ($request->filled('pickup_type')) {
            $query->whereHas('routePlanner', fn ($q) => $q->where('pickup_type', $request->string('pickup_type')));
        }

        if ($request->filled('status')) {
            $status = $request->string('status');
            if ($status === 'scanned') {
                $query->where('scan_status', 'scanned');
            } elseif ($status === 'unscanned') {
                $query->whereIn('scan_status', ['unscanned', 'pending', 'not_scanned']);
            }
        }

        if ($request->filled('from') || $request->filled('to')) {
            $from = $request->filled('from') ? $request->date('from') : null;
            $to = $request->filled('to') ? $request->date('to') : null;
            $timestampColumn = $request->string('status') === 'scanned' ? 'scanned_at' : 'created_at';

            if ($from) {
                $query->whereDate($timestampColumn, '>=', $from);
            }
            if ($to) {
                $query->whereDate($timestampColumn, '<=', $to);
            }
        }

        $perPage = max(1, min(100, $request->integer('limit', 20)));
        $logs = $query->latest()->paginate($perPage);

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Assignment logs retrieved successfully',
            status_code: self::API_SUCCESS,
            data: $logs->toArray()
        );
    }

    public function register(RegisterRoute $request)
    {
        $user = $request->user();
        $data = $request->validated();

        if (isset($user->provider_slug)) {
            $data['provider_slug'] = $this->resolveProviderScopeSlug($user);
        }

        if (empty($data['provider_slug'])) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: 'provider_slug is required',
                status_code: self::API_FAIL,
                data: []
            );
        }

        try {
            $routePlanner = $this->routePlannerService->createPlan($data);

            return self::apiResponse(
                in_error: false,
                message: 'Action Successful',
                reason: 'Route created successfully',
                status_code: self::API_SUCCESS,
                data: [
                    'assignment' => self::transformAssignment($routePlanner),
                ]
            );
        } catch (InvalidArgumentException $e) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: $e->getMessage(),
                status_code: self::API_FAIL,
                data: []
            );
        } catch (RuntimeException $e) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: $e->getMessage(),
                status_code: self::API_NOT_FOUND,
                data: []
            );
        } catch (\Throwable $e) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: 'Failed to create route: '.$e->getMessage(),
                status_code: self::API_FAIL,
                data: []
            );
        }
    }

    public function allPlans()
    {
        $user = Auth::user();

        $plans = RoutePlanner::query()
            ->with([
                'provider',
                'driver',
                'fleet',
                'pickups.client.group',
            ])
            ->where('provider_slug', $this->resolveProviderScopeSlug($user))
            ->latest()
            ->get();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Routes retrieved successfully',
            status_code: self::API_SUCCESS,
            data: [
                'assignments' => self::transformAssignmentsList($plans),
            ]
        );
    }

    public function show(RoutePlanner $plan)
    {
        $user = request()->user();
        if (isset($user->provider_slug) && $plan->provider_slug !== $this->resolveProviderScopeSlug($user)) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: 'Unauthorized to view this plan',
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
                    message: 'Action Failed',
                    reason: 'Plan not found in your jurisdiction',
                    status_code: self::API_NOT_FOUND,
                    data: []
                );
            }
        }

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Route details retrieved successfully',
            status_code: self::API_SUCCESS,
            data: [
                'assignment' => self::transformAssignment($plan),
            ]
        );
    }

    public function updateStatus(RouteStatusUpdate $request)
    {
        $data = $request->validated();
        $user = $request->user();

        $routePlanner = RoutePlanner::query()
            ->where('id', $data['id'])
            ->when(isset($user->provider_slug), fn ($q) => $q->where('provider_slug', $this->resolveProviderScopeSlug($user)))
            ->first();

        if (! $routePlanner) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: 'Route plan not found',
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        $routePlanner->status = $data['status'];
        $routePlanner->save();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Route planner status updated successfully',
            status_code: self::API_SUCCESS,
            data: [
                'assignment' => self::transformAssignment($routePlanner),
            ]
        );
    }

    public function updatePlan(RouteDetailsUpdate $request, RoutePlanner $plan)
    {
        $data = $request->validated();
        $user = $request->user();

        if (isset($user->provider_slug) && (string) $plan->provider_slug !== (string) $this->resolveProviderScopeSlug($user)) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: 'Unauthorized to update this plan',
                status_code: self::API_FAIL,
                data: []
            );
        }

        if (isset($user->provider_slug)) {
            if (isset($data['driver_slug']) && ! Driver::query()
                ->where('driver_slug', $data['driver_slug'])
                ->where('provider_slug', $this->resolveProviderScopeSlug($user))
                ->exists()) {
                return self::apiResponse(true, 'Action Failed', 'Unauthorized driver for this provider', self::API_FAIL, []);
            }

            if (isset($data['fleet_slug']) && ! Fleet::query()
                ->where('fleet_slug', $data['fleet_slug'])
                ->where('provider_slug', $this->resolveProviderScopeSlug($user))
                ->exists()) {
                return self::apiResponse(true, 'Action Failed', 'Unauthorized fleet for this provider', self::API_FAIL, []);
            }
        }

        unset($data['group_slug']);

        $plan->update($data);

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Route details updated successfully',
            status_code: self::API_SUCCESS,
            data: [
                'assignment' => self::transformAssignment($plan),
            ]
        );
    }

    public function deletePlan(RoutePlanner $plan)
    {
        $user = request()->user();
        if (isset($user->provider_slug) && (string) $plan->provider_slug !== (string) $this->resolveProviderScopeSlug($user)) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: 'Unauthorized to delete this plan',
                status_code: self::API_FAIL,
                data: []
            );
        }

        $plan->delete();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Route deleted successfully',
            status_code: self::API_SUCCESS,
            data: []
        );
    }

    /**
     * Admin: list all route planner records for a given provider.
     */
    public function routerplannerRecords(Provider $provider)
    {
        $plans = RoutePlanner::query()
            ->with([
                'provider',
                'driver',
                'fleet',
                'pickups.client.group',
            ])
            ->where('provider_slug', $provider->provider_slug)
            ->latest()
            ->get();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Provider route planner records retrieved successfully',
            status_code: self::API_SUCCESS,
            data: [
                'assignments' => self::transformAssignmentsList($plans),
            ]
        );
    }

    /**
     * Admin: map data for a single route planner record — all pickup stops with
     * client coordinates (latitude/longitude/gps_address) for plotting on a map.
     */
    public function routerplannerPickups(Provider $provider, RoutePlanner $routerplanner)
    {
        if ((string) $routerplanner->provider_slug !== (string) $provider->provider_slug) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: 'Route plan not found for this provider',
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Route planner pickups retrieved successfully',
            status_code: self::API_SUCCESS,
            data: self::transformRoutePlannerMap($routerplanner)
        );
    }
}
