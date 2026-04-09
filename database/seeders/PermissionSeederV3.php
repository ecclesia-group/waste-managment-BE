<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionSeederV3 extends Seeder
{
    public function run(): void
    {
        $actions = ['View', 'Create', 'Edit', 'Delete', 'Manage'];

        $modulesByActor = [
            'provider' => [
                'Dashboard', 'Customers', 'Drivers', 'Fleet_Management', 'Route_Planner',
                'Pickup', 'Payment_Management', 'Weighbridge_Records', 'Teams', 'Reports_Analytics',
            ],
            'facility' => [
                'Dashboard', 'Weighbridge', 'Payment_Management', 'Reports_Analytics', 'Teams',
            ],
            'district_assembly' => [
                'Dashboard', 'Provider_Management', 'Facility_Management', 'Zone_Management',
                'Complaints', 'Onboarding', 'Reports_Analytics', 'Teams',
            ],
            'admin' => [
                'Dashboard', 'Provider_Management', 'Zone_Management', 'Complaints_Management',
                'MMDA_Management', 'Facility_Management', 'Onboarding', 'Reports_Analytics',
                'Order_Management', 'Banner_Guide_Management', 'Teams',
            ],
        ];

        foreach ($modulesByActor as $actor => $modules) {
            foreach ($modules as $module) {
                foreach ($actions as $action) {
                    $name = "{$action}_{$module}";

                    Permission::query()->updateOrCreate(
                        ['actor' => $actor, 'name' => $name],
                        [
                            'module' => $module,
                            'permission_slug' => Permission::query()
                                ->where('actor', $actor)
                                ->where('name', $name)
                                ->value('permission_slug') ?? (string) Str::uuid(),
                        ]
                    );
                }
            }
        }
    }
}

