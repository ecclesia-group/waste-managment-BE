<?php
namespace Database\Seeders\SuperAdministrator;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CreateSuperAdministrator extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::firstOrCreate([
            "admin_slug"        => Str::uuid(),
            "first_name"        => "Super",
            "last_name"         => "Administrator",
            "phone_number"      => "233556906969",
            "email"             => "kankamthomas6@gmail.com",
            "password"          => "Passw0rd@12345",
            'email_verified_at' => now(),
            'profile_image'     => "https://media.istockphoto.com/id/1495088043/vector/user-profile-icon-avatar-or-person-icon-profile-picture-portrait-symbol-default-portrait.webp?s=1024x1024&w=is&k=20&c=oGqYHhfkz_ifeE6-dID6aM7bLz38C6vQTy1YcbgZfx8=",
        ]);
    }
}
