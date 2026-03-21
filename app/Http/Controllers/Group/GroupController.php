<?php
namespace App\Http\Controllers\Group;

use App\Http\Controllers\Controller;
use App\Http\Requests\Group\GroupCreation;
use App\Http\Requests\Group\GroupStatusUpdate;
use App\Http\Requests\Group\GroupUpdation;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GroupController extends Controller
{
    // Lists all groups
    public function allGroups()
    {
        $user   = Auth::guard('provider')->user();
        $groups = Group::where('provider_slug', $user->provider_slug)->get();
        if ($groups->isEmpty()) {
            return self::apiResponse(
                in_error: true,
                message: "No Groups Found",
                reason: "No groups are registered in the system",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Groups retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $groups->toArray()
        );
    }

    // Gets details of a single group
    public function show(Group $group)
    {
        $user = Auth::guard('provider')->user();
        if (isset($user->provider_slug) && (string) $group->provider_slug !== (string) $user->provider_slug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to view this group",
                status_code: self::API_FAIL,
                data: []
            );
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Group details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $group->toArray()
        );
    }

    // Creates a new group
    public function register(GroupCreation $request)
    {
        $user                  = Auth::guard('provider')->user();
        $data                  = $request->validated();
        $data['group_slug']    = Str::uuid();
        $data['provider_slug'] = $user->provider_slug;
        $group                 = Group::create($data);
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Group created successfully",
            status_code: self::API_SUCCESS,
            data: $group->toArray()
        );
    }

    // Updates an existing group
    public function updateGroup(GroupUpdation $request, Group $group)
    {
        $user = Auth::guard('provider')->user();
        if (isset($user->provider_slug) && (string) $group->provider_slug !== (string) $user->provider_slug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to update this group",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $data = $request->validated();
        $group->update($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Group updated successfully",
            status_code: self::API_SUCCESS,
            data: $group->toArray()
        );
    }

    // Updates the status of a group
    public function updateGroupStatus(GroupStatusUpdate $request)
    {
        $data          = $request->validated();

        $user          = Auth::guard('provider')->user();
        $group         = Group::query()
            ->where('group_slug', $data['group_slug'])
            ->where('provider_slug', $user->provider_slug)
            ->first();

        if (! $group) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Group not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }
        $group->status = $data['status'];
        $group->save();
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Group status updated successfully",
            status_code: self::API_SUCCESS,
            data: $group->toArray()
        );
    }

    // Deletes a group
    public function deleteGroup(Group $group)
    {
        $user = Auth::guard('provider')->user();
        if (isset($user->provider_slug) && (string) $group->provider_slug !== (string) $user->provider_slug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to delete this group",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $group->delete();
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Group deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }
}
