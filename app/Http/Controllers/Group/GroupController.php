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
    public function allGroups()
    {
        $user = Auth::guard('provider')->user();
        $providerSlug = self::providerScopeSlug($user);

        return $this->paginatedApiResponse(
            Group::query()
                ->forProvider((string) $providerSlug)
                ->orderByDesc('created_at')
                ->paginate($this->perPage(request())),
            'Groups retrieved successfully'
        );
    }

    public function show(Group $group)
    {
        $user = Auth::guard('provider')->user();
        if ((string) $group->provider_slug !== (string) self::providerScopeSlug($user)) {
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

    public function register(GroupCreation $request)
    {
        $user = Auth::guard('provider')->user();
        $data = $request->validated();
        $data['group_slug'] = Str::uuid();
        $data['provider_slug'] = self::providerScopeSlug($user);
        $data['status'] = 'active';
        $group = Group::create($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Group created successfully",
            status_code: self::API_SUCCESS,
            data: $group->toArray()
        );
    }

    public function updateGroup(GroupUpdation $request, Group $group)
    {
        $user = Auth::guard('provider')->user();
        if ((string) $group->provider_slug !== (string) self::providerScopeSlug($user)) {
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

    public function updateGroupStatus(GroupStatusUpdate $request)
    {
        $data = $request->validated();
        $user = Auth::guard('provider')->user();
        $providerSlug = self::providerScopeSlug($user);
        $group = Group::query()
            ->where('group_slug', $data['group_slug'])
            ->forProvider((string) $providerSlug)
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

    public function deleteGroup(Group $group)
    {
        $user = Auth::guard('provider')->user();
        if ((string) $group->provider_slug !== (string) self::providerScopeSlug($user)) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to delete this group",
                status_code: self::API_FAIL,
                data: []
            );
        }

        if ($group->clients()->exists()) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Cannot delete a group that has clients assigned",
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
