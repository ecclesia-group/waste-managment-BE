<?php
namespace App\Http\Controllers\RoutePlanner;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoutePlanner\RegisterRoute;
use App\Http\Requests\RoutePlanner\RouteDetailsUpdate;
use App\Http\Requests\RoutePlanner\RouteStatusUpdate;
use App\Models\Provider;
use App\Models\RoutePlanner;
use App\Models\Pickup;
use App\Services\RoutePlannerService;
use App\Traits\PaginatesApiResults;
use App\Traits\TransformsRoutePlannerResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use RuntimeException;

class RoutePlannerManagement extends Controller
{
    use PaginatesApiResults;
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

    // public function assignmentLogs(Request $request)
    // {
    //     $user = $request->user();

    //     $query = Pickup::query()
    //         ->whereNotNull('route_planner_id')
    //         ->with(['client', 'routePlanner']);

    //     if (isset($user->provider_slug)) {
    //         $query->where('provider_slug', $this->resolveProviderScopeSlug($user));
    //     }

    //     if (isset($user->driver_slug)) {
    //         $query->whereHas('routePlanner', fn ($q) => $q->where('driver_slug', $user->driver_slug));
    //     }

    //     if (isset($user->district_assembly_slug)) {
    //         $query->whereIn('provider_slug', function ($q) use ($user) {
    //             $q->select('provider_slug')
    //                 ->from('providers')
    //                 ->where('district_assembly', $user->district_assembly);
    //         });
    //     }

    //     if ($request->filled('provider_slug')) {
    //         $query->where('provider_slug', $request->string('provider_slug'));
    //     }
    //     if ($request->filled('driver_slug')) {
    //         $query->whereHas('routePlanner', fn ($q) => $q->where('driver_slug', $request->string('driver_slug')));
    //     }
    //     if ($request->filled('group_slug')) {
    //         $query->where('group_slug', $request->string('group_slug'));
    //     }
    //     if ($request->filled('route_planner_id')) {
    //         $query->where('route_planner_id', $request->integer('route_planner_id'));
    //     }
    //     if ($request->filled('pickup_type')) {
    //         $query->whereHas('routePlanner', fn ($q) => $q->where('pickup_type', $request->string('pickup_type')));
    //     }

    //     if ($request->filled('status')) {
    //         $status = $request->string('status');
    //         if ($status === 'scanned') {
    //             $query->where('scan_status', 'scanned');
    //         } elseif ($status === 'unscanned') {
    //             $query->whereIn('scan_status', ['unscanned', 'pending', 'not_scanned']);
    //         }
    //     }

    //     if ($request->filled('from') || $request->filled('to')) {
    //         $from = $request->filled('from') ? $request->date('from') : null;
    //         $to = $request->filled('to') ? $request->date('to') : null;
    //         $timestampColumn = $request->string('status') === 'scanned' ? 'scanned_at' : 'created_at';

    //         if ($from) {
    //             $query->whereDate($timestampColumn, '>=', $from);
    //         }
    //         if ($to) {
    //             $query->whereDate($timestampColumn, '<=', $to);
    //         }
    //     }

    //     $perPage = max(1, min(100, $request->integer('limit', 20)));
    //     $logs = $query->latest()->paginate($perPage);

    //     return self::apiResponse(
    //         in_error: false,
    //         message: 'Action Successful',
    //         reason: 'Assignment logs retrieved successfully',
    //         status_code: self::API_SUCCESS,
    //         data: $logs->toArray()
    //     );
    // }

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
                data: self::transformRoutePlannerSummary($routePlanner)
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

        $paginator = RoutePlanner::query()
            ->with(['driver', 'fleet'])
            ->withCount([
                'pickups as total_pickups',
                'pickups as scanned_pickups' => fn ($query) => $query->where('scan_status', 'scanned'),
            ])
            ->where('provider_slug', $this->resolveProviderScopeSlug($user))
            ->latest()
            ->paginate($this->perPage(request()));

        $paginator->setCollection(
            collect(self::transformRoutePlannersList($paginator->getCollection()))
        );

        return $this->paginatedApiResponse($paginator, 'Routes retrieved successfully', 'assignments');
    }

    public function show(RoutePlanner $plan)
    {
        if ($denied = $this->denyIfCannotAccessPlan($plan)) {
            return $denied;
        }

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Route details retrieved successfully',
            status_code: self::API_SUCCESS,
            data: self::transformRoutePlannerSummary($plan)
        );
    }

    public function planPickups(Request $request, RoutePlanner $plan)
    {
        if ($denied = $this->denyIfCannotAccessPlan($plan)) {
            return $denied;
        }

        return $this->paginatedApiResponse(
            $this->pickupsPaginatorForPlan($request, $plan),
            'Route planner pickups retrieved successfully'
        );
    }

    public function pickupDetails(RoutePlanner $plan, Pickup $pickup)
    {
        if ($denied = $this->denyIfCannotAccessPlan($plan)) {
            return $denied;
        }

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Pickup details retrieved successfully',
            status_code: self::API_SUCCESS,
            data: self::transformPickupStop($pickup)
        );
    }

    // public function updateStatus(RouteStatusUpdate $request)
    // {
    //     $data = $request->validated();
    //     $user = $request->user();

    //     $routePlanner = RoutePlanner::query()
    //         ->where('id', $data['id'])
    //         ->when(isset($user->provider_slug), fn ($q) => $q->where('provider_slug', $this->resolveProviderScopeSlug($user)))
    //         ->first();

    //     if (! $routePlanner) {
    //         return self::apiResponse(
    //             in_error: true,
    //             message: 'Action Failed',
    //             reason: 'Route plan not found',
    //             status_code: self::API_NOT_FOUND,
    //             data: []
    //         );
    //     }

    //     $routePlanner->status = $data['status'];
    //     $routePlanner->save();

    //     return self::apiResponse(
    //         in_error: false,
    //         message: 'Action Successful',
    //         reason: 'Route planner status updated successfully',
    //         status_code: self::API_SUCCESS,
    //         data: self::transformRoutePlannerSummary($routePlanner)
    //     );
    // }

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

        try {
            $plan = $this->routePlannerService->updatePlan($plan, $data);

            return self::apiResponse(
                in_error: false,
                message: 'Action Successful',
                reason: 'Route details updated successfully',
                status_code: self::API_SUCCESS,
                data: self::transformRoutePlannerSummary($plan)
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
                reason: 'Failed to update route: '.$e->getMessage(),
                status_code: self::API_FAIL,
                data: []
            );
        }
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

        $this->routePlannerService->deletePlan($plan);

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Route deleted successfully',
            status_code: self::API_SUCCESS,
            data: []
        );
    }

    public function routerplannerRecords(Request $request, Provider $provider)
    {
        $paginator = RoutePlanner::query()
            ->where('provider_slug', $provider->provider_slug)
            ->with(['driver', 'fleet'])
            ->withCount([
                'pickups as total_pickups',
                'pickups as scanned_pickups' => fn ($query) => $query->where('scan_status', 'scanned'),
            ])
            ->latest()
            ->paginate($this->perPage($request));

        $paginator->setCollection(
            collect(self::transformRoutePlannersList($paginator->getCollection()))
        );

        return $this->paginatedApiResponse($paginator, 'Route planner records retrieved successfully');
    }

    public function routerplannerRecord(Request $request, Provider $provider, RoutePlanner $routerplanner)
    {
        if ($routerplanner->provider_slug !== $provider->provider_slug) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: 'Route planner not found for this provider',
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }
        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Route planner record retrieved successfully',
            status_code: self::API_SUCCESS,
            data: self::transformRoutePlannerSummary($routerplanner)
        );
    }

    public function routerplannerPickups(Request $request, Provider $provider, RoutePlanner $routerplanner)
    {
        if ($routerplanner->provider_slug !== $provider->provider_slug) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: 'Route planner not found for this provider',
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }
        return $this->paginatedApiResponse(
            $this->pickupsPaginatorForPlan($request, $routerplanner),
            'Route planner pickups retrieved successfully'
        );
    }

    private function pickupsPaginatorForPlan(Request $request, RoutePlanner $plan): LengthAwarePaginator
    {
        $plan->refresh();

        return Pickup::query()
            ->where('route_planner_id', $plan->id)
            ->with(['client.group'])
            ->latest()
            ->paginate($this->perPage($request))
            ->through(fn (Pickup $pickup) => self::transformPickupStop($pickup, $plan->id));
    }

    private function denyIfCannotAccessPlan(RoutePlanner $plan)
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

        if (isset($user->district_assembly)) {
            $allowed = Provider::query()
                ->where('provider_slug', $plan->provider_slug)
                ->where('district_assembly', $user->district_assembly)
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

        return null;
    }
}
