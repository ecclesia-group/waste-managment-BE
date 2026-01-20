<?php
namespace App\Http\Controllers\RoutePlanner;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoutePlanner\RegisterRoute;
use App\Http\Requests\RoutePlanner\RouteDetailsUpdate;
use App\Http\Requests\RoutePlanner\RouteStatusUpdate;
use App\Models\RoutePlanner;

class RoutePlannerManagement extends Controller
{
    public function register(RegisterRoute $request)
    {
        $data = $request->validated();

        $routePlanner = RoutePlanner::create($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Route created successfully",
            status_code: self::API_SUCCESS,
            data: $routePlanner->toArray()
        );
    }

    public function allPlans()
    {
        $user = auth()->user();

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
        // Load relations
        $plan->load([
            'client',
            'driver',
            'fleet',
            'group',
        ]);
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Route details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $plan->toArray()
        );
    }

    public function updateStatus(RouteStatusUpdate $request)
    {
        $data                 = $request->validated();
        $routePlanner         = RoutePlanner::where('id', $data['id'])->first();
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

        $plan->update($data);
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Route details updated successfully",
            status_code: self::API_SUCCESS,
            data: $plan->toArray()
        );
    }

    public function deletePlan(RoutePlanner $routePlanner)
    {
        $routePlanner->delete();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Route deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }
}
