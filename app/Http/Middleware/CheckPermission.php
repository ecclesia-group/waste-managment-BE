<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permissionSlug)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'data' => [
                    'status_code' => 401,
                    'message' => 'Action Failed',
                    'in_error' => true,
                    'reason' => 'Unauthenticated',
                    'data' => [],
                    'point_in_time' => now(),
                ],
            ], 401);
        }

        if ($user instanceof \App\Models\Client) {
            return $next($request);
        }

        $actor = method_exists($user, 'actorTypeForRbac') ? $user->actorTypeForRbac() : null;
        if (! $actor) {
            return response()->json([
                'data' => [
                    'status_code' => 403,
                    'message' => 'Action Failed',
                    'in_error' => true,
                    'reason' => 'Forbidden',
                    'data' => [],
                    'point_in_time' => now(),
                ],
            ], 403);
        }

        $permission = Permission::query()
            ->where('permission_slug', $permissionSlug)
            ->where('actor', $actor)
            ->first();

        if (! $permission) {
            return response()->json([
                'data' => [
                    'status_code' => 403,
                    'message' => 'Action Failed',
                    'in_error' => true,
                    'reason' => 'Permission not available for this actor',
                    'data' => [],
                    'point_in_time' => now(),
                ],
            ], 403);
        }

        if (method_exists($user, 'hasPermission') && $user->hasPermission($permissionSlug)) {
            return $next($request);
        }

        return response()->json([
            'data' => [
                'status_code' => 403,
                'message' => 'Action Failed',
                'in_error' => true,
                'reason' => 'Forbidden',
                'data' => [],
                'point_in_time' => now(),
            ],
        ], 403);
    }
}

