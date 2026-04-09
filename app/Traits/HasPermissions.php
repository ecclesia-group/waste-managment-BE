<?php

namespace App\Traits;

trait HasPermissions
{
    public function hasPermission(string $permissionSlug): bool
    {
        // Client bypasses RBAC completely by requirement.
        if ($this instanceof \App\Models\Client) {
            return true;
        }

        if ((bool) ($this->is_main ?? true) === true) {
            return true;
        }

        $actor = $this->actorTypeForRbac();
        $role = $this->role()->with('permissions')->first();
        if (! $role || (string) $role->actor !== (string) $actor) {
            return false;
        }

        return $role->permissions->contains(function ($permission) use ($permissionSlug, $actor) {
            return (string) $permission->permission_slug === (string) $permissionSlug
                && (string) $permission->actor === (string) $actor;
        });
    }

    public function actorTypeForRbac(): ?string
    {
        return match (true) {
            $this instanceof \App\Models\Admin => 'admin',
            $this instanceof \App\Models\Provider => 'provider',
            $this instanceof \App\Models\Facility => 'facility',
            $this instanceof \App\Models\DistrictAssembly => 'district_assembly',
            default => null,
        };
    }
}

