<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            'Dashboard',
            'Providers',
            'Facilities',
            'DistrictAssemblies',
            'Zones',
            'Groups',
            'Clients',
            'Drivers',
            'Fleets',
            'Pickups',
            'RoutePlanner',
            'Payments',
            'Complaints',
            'Violations',
            'Reports',
            'Notifications',
        ];

        $actions = ['View', 'Create', 'Edit', 'Delete', 'Manage'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                $name = "{$action}_{$module}";
                Permission::query()->firstOrCreate(
                    ['name' => $name],
                    [
                        'permission_slug' => (string) Str::uuid(),
                        'module' => $module,
                    ]
                );
            }
        }
    }
}

