<?php
namespace Database\Seeders\SuperAdministrator;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CreateSuperAdministrator extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = 'kankamthomas6@gmail.com';

        $admin = Admin::firstOrNew(['email' => $email]);
        $admin->fill([
            'admin_slug' => $admin->admin_slug ?: (string) Str::uuid(),
            'parent_slug' => null,
            'is_main' => true,
            'first_name' => 'Super',
            'last_name' => 'Administrator',
            'phone_number' => '233556906969',
            'password' => 'Passw0rd@12345',
            'email_verified_at' => now(),
            'profile_image' => 'https://media.istockphoto.com/id/1495088043/vector/user-profile-icon-avatar-or-person-icon-profile-picture-portrait-symbol-default-portrait.webp?s=1024x1024&w=is&k=20&c=oGqYHhfkz_ifeE6-dID6aM7bLz38C6vQTy1YcbgZfx8=',
        ]);
        $admin->save();

        $role = Role::firstOrNew([
            'actor' => 'admin',
            'actor_slug' => $admin->actorSlugValue(),
            'name' => 'Super Administrator',
        ]);
        $role->role_slug = $role->role_slug ?: (string) Str::uuid();
        $role->save();

        // Assign a baseline set of admin permissions and keep role_permission in sync.
        $permissionNames = [
            'View_Dashboard_Details',
            'View_Assignment_Logs',
            'View_Provider',
            'Edit_Provider',
            'View_Assigned_Location',
            'View_Facility',
            'Edit_Facility',
            'Suspend_Facility',
            'Invite_User',
            'View_Onboarding',
            'Edit_Onboarding',
            'Suspend_Onboarding',
            'Deactivate_Onboarding',
            'Add_Team',
            'View_Team',
            'Edit_Team',
            'Deactivate_Team',
        ];

        $permissionIds = Permission::query()
            ->where('actor', 'admin')
            ->whereIn('name', $permissionNames)
            ->pluck('id')
            ->toArray();

        // Fallback: ensure super admin role is never left without permissions.
        if ($permissionIds === []) {
            $permissionIds = Permission::query()
                ->where('actor', 'admin')
                ->pluck('id')
                ->toArray();
        }

        $role->permissions()->sync($permissionIds);

        // Ensure the admin account points to this role.
        $admin->role_slug = $role->role_slug;
        $admin->save();
    }
}
