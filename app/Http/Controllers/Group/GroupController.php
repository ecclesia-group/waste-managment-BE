<?php
namespace App\Http\Controllers\Group;

use App\Http\Controllers\Controller;
use App\Http\Requests\Group\GroupCreation;
use App\Http\Requests\Group\GroupStatusUpdate;
use App\Http\Requests\Group\GroupUpdation;
use App\Models\Group;
use Illuminate\Support\Str;

class GroupController extends Controller
{
    // Lists all groups
    public function allGroups()
    {
        $groups = Group::all();
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
        $data                  = $request->validated();
        $data['group_slug']    = Str::uuid();
        $data['provider_slug'] = $request->user()->provider_slug;
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
        $group         = Group::where('group_slug', $data['group_slug'])->first();
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
