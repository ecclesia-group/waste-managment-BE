<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionSeederV3 extends Seeder
{
    public function run(): void
    {
        $permissionsByActor = [
            'admin' => [
                ['module' => 'Dashboard', 'name' => 'View_Dashboard_Details'],
                ['module' => 'Dashboard', 'name' => 'View_Assignment_Details'],
                ['module' => 'Dashboard', 'name' => 'View_Assignment_Logs'],

                ['module' => 'Provider_Management', 'name' => 'View_Provider'],
                ['module' => 'Provider_Management', 'name' => 'Edit_Provider'],
                ['module' => 'Provider_Management', 'name' => 'Suspend_Provider'],

                ['module' => 'Zone_Management', 'name' => 'View_Assigned_Location'],
                ['module' => 'Zone_Management', 'name' => 'Revoke_Zone'],

                ['module' => 'Facilities_Management', 'name' => 'Suspend_Facility'],
                ['module' => 'Facilities_Management', 'name' => 'View_Facility'],
                ['module' => 'Facilities_Management', 'name' => 'Edit_Facility'],
                ['module' => 'Facilities_Management', 'name' => 'Deactivate_Facility'],

                ['module' => 'Complaints', 'name' => 'View_Details'],

                ['module' => 'Onboarding', 'name' => 'Invite_User'],
                ['module' => 'Onboarding', 'name' => 'View_Onboarding'],
                ['module' => 'Onboarding', 'name' => 'Edit_Onboarding'],
                ['module' => 'Onboarding', 'name' => 'Deactivate_Onboarding'],
                ['module' => 'Onboarding', 'name' => 'Suspend_Onboarding'],

                ['module' => 'Teams', 'name' => 'Add_Team'],
                ['module' => 'Teams', 'name' => 'View_Team'],
                ['module' => 'Teams', 'name' => 'Edit_Team'],
                ['module' => 'Teams', 'name' => 'Deactivate_Team'],
            ],
            'provider' => [
                ['module' => 'Dashboard', 'name' => 'View_Dashboard'],
                ['module' => 'Dashboard', 'name' => 'Handover_Request'],

                ['module' => 'Customer', 'name' => 'Add_Customer'],
                ['module' => 'Customer', 'name' => 'View_Customer'],
                ['module' => 'Customer', 'name' => 'Edit_Customer'],
                ['module' => 'Customer', 'name' => 'Deactivate_Customer'],
                ['module' => 'Customer', 'name' => 'Assign_Bin_Code'],
                ['module' => 'Customer', 'name' => 'Schedule_Pickup'],

                ['module' => 'Drivers', 'name' => 'Add_Driver'],
                ['module' => 'Drivers', 'name' => 'View_Driver'],
                ['module' => 'Drivers', 'name' => 'Edit_Driver'],
                ['module' => 'Drivers', 'name' => 'Deactivate_Driver'],
                ['module' => 'Drivers', 'name' => 'Change_Status'],

                ['module' => 'Fleet_Management', 'name' => 'Add_Fleet'],
                ['module' => 'Fleet_Management', 'name' => 'View_Fleet'],
                ['module' => 'Fleet_Management', 'name' => 'Edit_Fleet'],
                ['module' => 'Fleet_Management', 'name' => 'Deactivate_Fleet'],
                ['module' => 'Fleet_Management', 'name' => 'Change_Status'],

                ['module' => 'Pickup_Planner', 'name' => 'View_Assign_Pickup_Details'],
                ['module' => 'Pickup_Planner', 'name' => 'Assign_Pickup'],
                ['module' => 'Pickup_Planner', 'name' => 'View_Assignment_Logs'],
                ['module' => 'Pickup_Planner', 'name' => 'View_Pickup_Planner'],
                ['module' => 'Pickup_Planner', 'name' => 'Start_Pickups'],
                ['module' => 'Pickup_Planner', 'name' => 'Scan_Bin'],
                ['module' => 'Pickup_Planner', 'name' => 'Issue_Command'],
                ['module' => 'Pickup_Planner', 'name' => 'View_Assigned_Customers'],
                ['module' => 'Pickup_Planner', 'name' => 'View_Scanned_Customers'],

                ['module' => 'Payment_Management', 'name' => 'View_Payment'],
                ['module' => 'Payment_Management', 'name' => 'View_Handover_Logs'],
                ['module' => 'Report_Analytics', 'name' => 'View_Report_Analytics'],
                ['module' => 'Weighbridge_Records', 'name' => 'View_Weighbridge_Records'],

                ['module' => 'Complaints', 'name' => 'View_Complaints'],
                ['module' => 'Complaints', 'name' => 'Change_Status'],

                ['module' => 'Teams', 'name' => 'Add_Team'],
                ['module' => 'Teams', 'name' => 'View_Team'],
                ['module' => 'Teams', 'name' => 'Edit_Team'],
                ['module' => 'Teams', 'name' => 'Deactivate_Team'],
            ],
            'facility' => [
                ['module' => 'Dashboard', 'name' => 'View_Dashboard'],
                ['module' => 'Weighbridge', 'name' => 'Scan_Weighbridge'],
                ['module' => 'Weighbridge', 'name' => 'View_Current_Scan'],
                ['module' => 'Weighbridge', 'name' => 'View_Previous_Scan'],
                ['module' => 'Payment_Management', 'name' => 'View_Payment'],
                ['module' => 'Report_Analytics', 'name' => 'View_Report_Analytics'],
                ['module' => 'Teams', 'name' => 'Add_Team'],
                ['module' => 'Teams', 'name' => 'View_Team'],
                ['module' => 'Teams', 'name' => 'Edit_Team'],
                ['module' => 'Teams', 'name' => 'Deactivate_Team'],
            ],
            'district_assembly' => [
                ['module' => 'Dashboard', 'name' => 'View_Dashboard'],
                ['module' => 'Dashboard', 'name' => 'View_Assignment_Details'],
                ['module' => 'Dashboard', 'name' => 'View_Assignment_Logs'],

                ['module' => 'Provider_Management', 'name' => 'View_Provider'],
                ['module' => 'Provider_Management', 'name' => 'Edit_Provider'],
                ['module' => 'Provider_Management', 'name' => 'Suspend_Provider'],

                ['module' => 'Zone_Management', 'name' => 'View_Assign_Location'],
                ['module' => 'Zone_Management', 'name' => 'Revoke_Zone'],

                ['module' => 'Facilities_Management', 'name' => 'Revoke_Facility'],
                ['module' => 'Facilities_Management', 'name' => 'View_Facility'],
                ['module' => 'Facilities_Management', 'name' => 'Edit_Facility'],
                ['module' => 'Facilities_Management', 'name' => 'Suspend_Facility'],

                ['module' => 'Report_Analytics', 'name' => 'View_Report_Analytics'],
                ['module' => 'Complaints', 'name' => 'View_Details'],

                ['module' => 'Onboarding', 'name' => 'Invite_User'],
                ['module' => 'Onboarding', 'name' => 'View_Onboarding'],
                ['module' => 'Onboarding', 'name' => 'Edit_Onboarding'],
                ['module' => 'Onboarding', 'name' => 'Suspend_Onboarding'],
                ['module' => 'Onboarding', 'name' => 'Deactivate_Onboarding'],

                ['module' => 'Teams', 'name' => 'Add_Team'],
                ['module' => 'Teams', 'name' => 'View_Team'],
                ['module' => 'Teams', 'name' => 'Edit_Team'],
                ['module' => 'Teams', 'name' => 'Deactivate_Team'],
            ],
        ];

        foreach ($permissionsByActor as $actor => $permissions) {
            $intendedNames = [];

            foreach ($permissions as $permission) {
                $name = $permission['name'];
                $module = $permission['module'];
                $intendedNames[] = $name;

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

            Permission::query()
                ->where('actor', $actor)
                ->whereNotIn('name', $intendedNames)
                ->delete();
        }
    }
}
