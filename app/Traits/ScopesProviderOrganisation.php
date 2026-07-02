<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait ScopesProviderOrganisation
{
    public function scopeForProvider(Builder $query, string $providerSlug, string $column = 'provider_slug'): Builder
    {
        return $query->where($column, $providerSlug);
    }
}
