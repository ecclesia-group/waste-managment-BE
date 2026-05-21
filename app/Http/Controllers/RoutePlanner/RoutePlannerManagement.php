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
use App\Models\Group;
use App\Models\Pickup;
use App\Models\Provider;
use App\Models\RoutePlanner;
use App\Models\RoutePlannerBinAssignment;
use App\Traits\TransformsRoutePlannerResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoutePlannerManagement extends Controller
{
    use TransformsRoutePlannerResponse;

    public function assignmentLogs(Request $request)
    {
        $user = $request->user();

        $query = RoutePlannerBinAssignment::query()
            ->with(['client', 'pickup', 'routePlanner']);

        if (isset($user->provider_slug)) {
            $query->where('provider_slug', $this->resolveProviderScopeSlug($user));
        }

        if (isset($user->driver_slug)) {
            $query->where('driver_slug', $user->driver_slug);
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
            $query->where('driver_slug', $request->string('driver_slug'));
        }
        if ($request->filled('group_slug')) {
            $query->where('group_slug', $request->string('group_slug'));
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

        if (isset($user->provider_slug)) {
            $data['provider_slug'] = $this->resolveProviderScopeSlug($user);
        }

        if (empty($data['provider_slug'])) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "provider_slug is required",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $pickupType = (string) $data['pickup_type'];
        $pickupDate = Carbon::parse($data['pickup_date']);
        $selectedGroupSlugs = collect($data['group_slugs'] ?? [])->filter()->unique()->values();
        $selectedClientSlugs = collect($data['client_slugs'] ?? [])->filter()->unique()->values();
        $selectedBulkCodes = collect($data['bulk_request_codes'] ?? [])->filter()->unique()->values();

        if ($pickupType === 'normal' && $selectedGroupSlugs->isEmpty() && $selectedClientSlugs->isEmpty()) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "For normal pickup plans, provide group_slugs or client_slugs",
                status_code: self::API_FAIL,
                data: []
            );
        }

        if ($pickupType === 'bulk_waste_request'
            && $selectedBulkCodes->isEmpty()
            && $selectedClientSlugs->isEmpty()) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "For bulk pickup plans, provide bulk_request_codes or client_slugs",
                status_code: self::API_FAIL,
                data: []
            );
        }

        DB::beginTransaction();
        try {
            if (! $this->authorizeDriverFleetGroups($data, $user)) {
                DB::rollBack();

                return self::apiResponse(
                    in_error: true,
                    message: "Action Failed",
                    reason: "Driver, fleet, or group is not authorized for this provider",
                    status_code: self::API_FAIL,
                    data: []
                );
            }

            $bulkRequestsByClientSlug = collect();
            if ($pickupType === 'bulk_waste_request') {
                $bulkQuery = BulkWasteRequest::query()
                    ->where('provider_slug', $data['provider_slug'])
                    ->where('status', 'approved');

                if ($selectedBulkCodes->isNotEmpty()) {
                    $bulkQuery->whereIn('request_code', $selectedBulkCodes->all());
                }
                if ($selectedClientSlugs->isNotEmpty()) {
                    $bulkQuery->whereIn('client_slug', $selectedClientSlugs->all());
                }

                $bulkRequests = $bulkQuery
                    ->orderByDesc('created_at')
                    ->get();

                if ($bulkRequests->isEmpty()) {
                    DB::rollBack();

                    return self::apiResponse(
                        in_error: true,
                        message: "Action Failed",
                        reason: "No approved bulk waste requests found for this provider",
                        status_code: self::API_NOT_FOUND,
                        data: []
                    );
                }

                $bulkRequestsByClientSlug = $bulkRequests->keyBy('client_slug');
                $selectedClientSlugs = $bulkRequestsByClientSlug->keys()->values();
            }

            $clients = Client::query()
                ->where('provider_slug', $data['provider_slug'])
                ->where('status', 'active')
                ->when($selectedClientSlugs->isNotEmpty(), fn ($q) => $q->whereIn('client_slug', $selectedClientSlugs->all()))
                ->when($pickupType === 'normal' && $selectedGroupSlugs->isNotEmpty(), fn ($q) => $q->whereIn('group_slug', $selectedGroupSlugs->all()))
                ->get();

            if ($clients->isEmpty()) {
                DB::rollBack();

                return self::apiResponse(
                    in_error: true,
                    message: "Action Failed",
                    reason: "No active clients found for this plan",
                    status_code: self::API_NOT_FOUND,
                    data: []
                );
            }

            $routePlanner = RoutePlanner::create([
                'provider_slug' => $data['provider_slug'],
                'driver_slug' => $data['driver_slug'],
                'fleet_slug' => $data['fleet_slug'],
                'group_slug' => $selectedGroupSlugs->first(),
                'pickup_date' => $pickupDate,
                'pickup_type' => $pickupType,
                'status' => $data['status'] ?? 'pending',
                'route_meta' => [
                    'pickup_type' => $pickupType,
                    'pickup_date' => $pickupDate->toISOString(),
                    'selected_group_slugs' => $selectedGroupSlugs->values()->all(),
                    'selected_client_slugs' => $selectedClientSlugs->values()->all(),
                    'selected_bulk_request_codes' => $selectedBulkCodes->values()->all(),
                ],
            ]);

            foreach ($clients as $client) {
                $bulkRequest = $bulkRequestsByClientSlug->get($client->client_slug);
                $pickupCode = self::generateUniquePickupCode();

                Pickup::create([
                    'code' => $pickupCode,
                    'bulk_waste_request_code' => $bulkRequest?->request_code,
                    'client_slug' => $client->client_slug,
                    'title' => $bulkRequest?->title ?? 'Scheduled pickup',
                    'category' => $pickupType === 'bulk_waste_request' ? 'bulk_waste_request' : 'normal_pickup',
                    'description' => $bulkRequest?->description,
                    'amount' => $bulkRequest?->amount,
                    'status' => 'scheduled',
                    'scan_status' => 'unscanned',
                    'location' => $client->pickup_location ?: ($client->gps_address ?: 'Unknown'),
                    'provider_slug' => $routePlanner->provider_slug,
                    'images' => $bulkRequest?->images,
                    'pickup_date' => $pickupDate,
                ]);

                if ($bulkRequest) {
                    $this->scheduleBulkRequest($bulkRequest, $pickupDate);
                }

                RoutePlannerBinAssignment::create([
                    'route_planner_id' => $routePlanner->id,
                    'provider_slug' => $routePlanner->provider_slug,
                    'driver_slug' => $routePlanner->driver_slug,
                    'fleet_slug' => $routePlanner->fleet_slug,
                    'group_slug' => $client->group_slug,
                    'client_slug' => $client->client_slug,
                    'pickup_code' => $pickupCode,
                    'scan_status' => 'unscanned',
                ]);
            }

            DB::commit();

            $routePlanner->load([
                'provider',
                'driver',
                'fleet',
                'assignments.client.group',
                'assignments.pickup',
            ]);

            return self::apiResponse(
                in_error: false,
                message: "Action Successful",
                reason: "Route created successfully",
                status_code: self::API_SUCCESS,
                data: self::transformPlanDetail($routePlanner)
            );
        } catch (\Throwable $e) {
            DB::rollBack();

            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Failed to create route: ".$e->getMessage(),
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
                'assignments.client.group',
                'assignments.pickup',
            ])
            ->where('provider_slug', $this->resolveProviderScopeSlug($user))
            ->latest()
            ->get();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Routes retrieved successfully",
            status_code: self::API_SUCCESS,
            data: [
                'plans' => self::transformPlansList($plans),
            ]
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

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Route details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: self::transformPlanDetail($plan)
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
            data: self::transformPlanDetail($routePlanner)
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

        if (isset($user->provider_slug)) {
            if (isset($data['driver_slug']) && ! Driver::query()
                ->where('driver_slug', $data['driver_slug'])
                ->where('provider_slug', $this->resolveProviderScopeSlug($user))
                ->exists()) {
                return self::apiResponse(true, "Action Failed", "Unauthorized driver for this provider", self::API_FAIL, []);
            }

            if (isset($data['fleet_slug']) && ! Fleet::query()
                ->where('fleet_slug', $data['fleet_slug'])
                ->where('provider_slug', $this->resolveProviderScopeSlug($user))
                ->exists()) {
                return self::apiResponse(true, "Action Failed", "Unauthorized fleet for this provider", self::API_FAIL, []);
            }
        }

        $plan->update($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Route details updated successfully",
            status_code: self::API_SUCCESS,
            data: self::transformPlanDetail($plan)
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

        $plan->delete();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Route deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }

    private function scheduleBulkRequest(BulkWasteRequest $bulkRequest, Carbon $pickupDate): void
    {
        $bulkRequest->pickup_date = $pickupDate;
        $bulkRequest->status = 'scheduled';

        if ($bulkRequest->payment_status === null) {
            $bulkRequest->payment_status = ((float) ($bulkRequest->amount ?? 0)) > 0 ? 'unpaid' : 'paid';
        }

        $bulkRequest->save();
    }

    private function authorizeDriverFleetGroups(array $data, object $user): bool
    {
        if (! isset($user->provider_slug)) {
            return true;
        }

        $providerSlug = $data['provider_slug'];

        if (! Driver::query()
            ->where('driver_slug', $data['driver_slug'])
            ->where('provider_slug', $providerSlug)
            ->exists()) {
            return false;
        }

        if (! Fleet::query()
            ->where('fleet_slug', $data['fleet_slug'])
            ->where('provider_slug', $providerSlug)
            ->exists()) {
            return false;
        }

        $groupSlugs = $data['group_slugs'] ?? [];
        if (! empty($groupSlugs)) {
            $count = Group::query()
                ->whereIn('group_slug', $groupSlugs)
                ->where('provider_slug', $providerSlug)
                ->count();

            if ($count !== count(array_unique($groupSlugs))) {
                return false;
            }
        }

        return true;
    }

    protected static function generateUniquePickupCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (Pickup::where('code', $code)->exists());

        return $code;
    }
}
