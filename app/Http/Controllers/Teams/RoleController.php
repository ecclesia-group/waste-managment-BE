<?php

namespace App\Http\Controllers\Teams;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $ownerSlug = $user->ownerSlug();
        $actor = $this->actorTypeFromUser($user);

        $roles = Role::query()
            ->with('permissions:id,permission_slug,name,module')
            ->where('actor', $actor)
            ->where('actor_slug', $ownerSlug)
            ->latest()
            ->get();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Roles retrieved successfully',
            status_code: self::API_SUCCESS,
            data: $roles->toArray()
        );
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (! (bool) ($user->is_main ?? true)) {
            return self::apiResponse(true, 'Action Failed', 'Only main accounts can create roles', self::API_FAIL, []);
        }

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'permission_slugs' => 'required|array|min:1',
            'permission_slugs.*' => 'required|string|exists:permissions,permission_slug',
        ]);

        $ownerSlug = $user->actorSlugValue();
        $actor = $this->actorTypeFromUser($user);

        $role = Role::create([
            'role_slug' => (string) Str::uuid(),
            'name' => $data['name'],
            'actor' => $actor,
            'actor_slug' => $ownerSlug,
        ]);

        $permissionIds = Permission::query()
            ->whereIn('permission_slug', $data['permission_slugs'])
            ->where('actor', $actor)
            ->pluck('id')
            ->toArray();

        if (count($permissionIds) !== count($data['permission_slugs'])) {
            return self::apiResponse(true, 'Action Failed', 'One or more permissions are invalid for this actor', self::API_FAIL, []);
        }

        $role->permissions()->sync($permissionIds);
        $role->load('permissions:id,permission_slug,name,module');

        return self::apiResponse(false, 'Action Successful', 'Role created successfully', self::API_CREATED, $role->toArray());
    }

    public function update(Request $request, string $roleSlug)
    {
        $user = $request->user();
        $ownerSlug = $user->ownerSlug();
        $actor = $this->actorTypeFromUser($user);

        $role = Role::query()
            ->where('role_slug', $roleSlug)
            ->where('actor', $actor)
            ->where('actor_slug', $ownerSlug)
            ->first();

        if (! $role) {
            return self::apiResponse(true, 'Action Failed', 'Role not found', self::API_NOT_FOUND, []);
        }

        if (! (bool) ($user->is_main ?? true)) {
            return self::apiResponse(true, 'Action Failed', 'Only main accounts can update roles', self::API_FAIL, []);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'permission_slugs' => 'sometimes|array|min:1',
            'permission_slugs.*' => 'required_with:permission_slugs|string|exists:permissions,permission_slug',
        ]);

        if (array_key_exists('name', $data)) {
            $role->name = $data['name'];
            $role->save();
        }

        if (array_key_exists('permission_slugs', $data)) {
            $permissionIds = Permission::query()
                ->whereIn('permission_slug', $data['permission_slugs'])
                ->where('actor', $actor)
                ->pluck('id')
                ->toArray();

            if (count($permissionIds) !== count($data['permission_slugs'])) {
                return self::apiResponse(true, 'Action Failed', 'One or more permissions are invalid for this actor', self::API_FAIL, []);
            }
            $role->permissions()->sync($permissionIds);
        }

        $role->load('permissions:id,permission_slug,name,module');

        return self::apiResponse(false, 'Action Successful', 'Role updated successfully', self::API_SUCCESS, $role->toArray());
    }

    public function permissions(Request $request)
    {
        $user = $request->user();
        $actor = $this->actorTypeFromUser($user);

        $permissionsByModule = Permission::query()
            ->where('actor', $actor)
            ->orderBy('module')
            ->orderBy('name')
            ->get(['module', 'name', 'permission_slug'])
            ->groupBy('module')
            ->map(fn ($items) => $items->map(fn ($p) => [
                'name' => $p->name,
                'slug' => $p->permission_slug,
            ])->values())
            ->toArray();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Permissions retrieved successfully',
            status_code: self::API_SUCCESS,
            data: $permissionsByModule
        );
    }

    public function destroy(Request $request, string $roleSlug)
    {
        $user = $request->user();
        $ownerSlug = $user->ownerSlug();
        $actor = $this->actorTypeFromUser($user);

        $role = Role::query()
            ->where('role_slug', $roleSlug)
            ->where('actor', $actor)
            ->where('actor_slug', $ownerSlug)
            ->first();

        if (! $role) {
            return self::apiResponse(true, 'Action Failed', 'Role not found', self::API_NOT_FOUND, []);
        }

        if (! (bool) ($user->is_main ?? true)) {
            return self::apiResponse(true, 'Action Failed', 'Only main accounts can delete roles', self::API_FAIL, []);
        }

        $memberModel = $this->memberModelFromActor($actor);
        $assignedCount = $memberModel::query()
            ->where('parent_slug', $ownerSlug)
            ->where('role_slug', $role->role_slug)
            ->count();

        if ($assignedCount > 0) {
            return self::apiResponse(true, 'Action Failed', 'Cannot delete role assigned to team members', self::API_FAIL, []);
        }

        $role->permissions()->detach();
        $role->delete();

        return self::apiResponse(false, 'Action Successful', 'Role deleted successfully', self::API_SUCCESS, []);
    }

    private function actorTypeFromUser($user): string
    {
        return match (true) {
            $user instanceof \App\Models\Admin => 'admin',
            $user instanceof \App\Models\Provider => 'provider',
            $user instanceof \App\Models\Facility => 'facility',
            default => 'district_assembly',
        };
    }

    private function memberModelFromActor(string $actor): string
    {
        return match ($actor) {
            'admin' => \App\Models\Admin::class,
            'provider' => \App\Models\Provider::class,
            'facility' => \App\Models\Facility::class,
            default => \App\Models\DistrictAssembly::class,
        };
    }
}
