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
            "email"             => "super.administrator@wms.com",
            "password"          => "Passw0rd@12345",
            'email_verified_at' => now(),
            'profile_image'     => null,
        ]);
    }
}
