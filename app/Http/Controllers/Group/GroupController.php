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
        $ownerSlug = self::ownerProviderSlug($user);

        return $this->paginatedApiResponse(
            Group::query()
                ->forProviderOrganisation((string) $ownerSlug)
                ->orderByDesc('created_at')
                ->paginate($this->perPage(request())),
            'Groups retrieved successfully'
        );
    }

    public function show(Group $group)
    {
        $user = Auth::guard('provider')->user();
        if (isset($user->provider_slug) && ! self::recordBelongsToProviderOrganisation($group->provider_slug, $user)) {
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
        $data['provider_slug'] = self::actorProviderSlug($user);
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
        if (isset($user->provider_slug) && ! self::recordBelongsToProviderOrganisation($group->provider_slug, $user)) {
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
        $ownerSlug = self::ownerProviderSlug($user);
        $group = Group::query()
            ->where('group_slug', $data['group_slug'])
            ->where('provider_slug', $ownerSlug)
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
        if (isset($user->provider_slug) && ! self::recordBelongsToProviderOrganisation($group->provider_slug, $user)) {
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
