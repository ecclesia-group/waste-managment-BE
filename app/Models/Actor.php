<?php
namespace App\Models;

use App\Traits\Helpers;
use App\Traits\HasPermissions;
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
}
