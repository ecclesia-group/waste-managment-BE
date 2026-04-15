<?php
namespace App\Models;

use App\Traits\Helpers;
use App\Traits\HasPermissions;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

class Actor extends Authenticatable implements MustVerifyEmail, OAuthenticatable
{
    use HasFactory, SoftDeletes, HasApiTokens, Notifiable, Helpers, HasPermissions;

    public function role(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Role::class, 'role_slug', 'role_slug');
    }

    public function ownerSlug(): ?string
    {
        return (bool) ($this->is_main ?? true)
            ? $this->actorSlugValue()
            : ($this->parent_slug ?: null);
    }

    public function actorSlugValue(): ?string
    {
        foreach (['admin_slug', 'provider_slug', 'facility_slug', 'district_assembly_slug'] as $field) {
            if (isset($this->{$field}) && ! empty($this->{$field})) {
                return (string) $this->{$field};
            }
        }

        return null;
    }

    public function rbacForFrontend(): array
    {
        $actorType = $this->actorTypeForRbac();
        if (! $actorType) {
            return [
                'role' => null,
                'permissions' => [],
            ];
        }

        if ((bool) ($this->is_main ?? true)) {
            $permissions = Permission::query()
                ->where('actor', $actorType)
                ->orderBy('module')
                ->orderBy('name')
                ->get(['permission_slug', 'name', 'module'])
                ->map(fn ($permission) => [
                    'slug' => $permission->permission_slug,
                    'name' => $permission->name,
                    'module' => $permission->module,
                ])
                ->values()
                ->toArray();

            return [
                'role' => [
                    'role_slug' => null,
                    'name' => 'main_account',
                    'is_main' => true,
                ],
                'permissions' => $permissions,
            ];
        }

        $role = $this->role()->with('permissions:id,permission_slug,name,module,actor')->first();
        if (! $role || (string) $role->actor !== (string) $actorType) {
            return [
                'role' => null,
                'permissions' => [],
            ];
        }

        return [
            'role' => [
                'role_slug' => $role->role_slug,
                'name' => $role->name,
                'is_main' => false,
            ],
            'permissions' => $role->permissions
                ->filter(fn ($permission) => (string) $permission->actor === (string) $actorType)
                ->map(fn ($permission) => [
                    'slug' => $permission->permission_slug,
                    'name' => $permission->name,
                    'module' => $permission->module,
                ])
                ->values()
                ->toArray(),
        ];
    }
}
