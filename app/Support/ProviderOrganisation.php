<?php

namespace App\Support;

use App\Models\Provider;
use Illuminate\Database\Eloquent\Builder;

class ProviderOrganisation
{
    public static function actorSlug(object $user): ?string
    {
        return isset($user->provider_slug) ? (string) $user->provider_slug : null;
    }

    public static function ownerSlugForUser(object $user): ?string
    {
        if (! isset($user->provider_slug)) {
            return null;
        }

        return (bool) ($user->is_main ?? true)
            ? (string) $user->provider_slug
            : (string) ($user->parent_slug ?: $user->provider_slug);
    }

    public static function actorContext(object $user): array
    {
        $isMain = (bool) ($user->is_main ?? true);

        return [
            'provider_slug' => self::actorSlug($user),
            'parent_slug' => $isMain ? null : ($user->parent_slug ?? null),
            'is_main' => $isMain,
            'owner_slug' => self::ownerSlugForUser($user),
        ];
    }

    public static function ownerSlug(?string $providerSlug): ?string
    {
        if ($providerSlug === null || $providerSlug === '') {
            return null;
        }

        $provider = Provider::query()->where('provider_slug', $providerSlug)->first();
        if ($provider === null) {
            return $providerSlug;
        }

        return (bool) ($provider->is_main ?? true)
            ? (string) $provider->provider_slug
            : (string) ($provider->parent_slug ?: $provider->provider_slug);
    }

    public static function recordBelongsToOrganisation(?string $recordProviderSlug, object $user): bool
    {
        $ownerSlug = self::ownerSlugForUser($user);
        if ($ownerSlug === null || $recordProviderSlug === null || $recordProviderSlug === '') {
            return false;
        }

        if ((string) $recordProviderSlug === (string) $ownerSlug) {
            return true;
        }

        return Provider::query()
            ->where('provider_slug', $recordProviderSlug)
            ->where('parent_slug', $ownerSlug)
            ->exists();
    }

    public static function slugsShareOrganisation(?string $slugA, ?string $slugB): bool
    {
        if ($slugA === null || $slugB === null || $slugA === '' || $slugB === '') {
            return false;
        }

        return self::ownerSlug($slugA) === self::ownerSlug($slugB);
    }

    public static function organisationSlugList(string $ownerProviderSlug): array
    {
        $teamSlugs = Provider::query()
            ->where('parent_slug', $ownerProviderSlug)
            ->pluck('provider_slug')
            ->all();

        return array_values(array_unique(array_merge([$ownerProviderSlug], $teamSlugs)));
    }

    public static function scopeQuery(Builder $query, string $ownerProviderSlug, string $column = 'provider_slug'): Builder
    {
        return $query->where(function ($q) use ($ownerProviderSlug, $column) {
            $q->where($column, $ownerProviderSlug)
                ->orWhereIn($column, function ($sub) use ($ownerProviderSlug) {
                    $sub->select('provider_slug')
                        ->from('providers')
                        ->where('parent_slug', $ownerProviderSlug);
                });
        });
    }
}
