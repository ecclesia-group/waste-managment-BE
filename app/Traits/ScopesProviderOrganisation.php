<?php

namespace App\Traits;

use App\Support\ProviderOrganisation;
use Illuminate\Database\Eloquent\Builder;

trait ScopesProviderOrganisation
{
    public function scopeForProviderOrganisation(Builder $query, string $ownerProviderSlug, string $column = 'provider_slug'): Builder
    {
        return ProviderOrganisation::scopeQuery($query, $ownerProviderSlug, $column);
    }
}
