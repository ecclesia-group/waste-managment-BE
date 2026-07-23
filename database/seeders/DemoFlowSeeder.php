<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Client;
use App\Models\DistrictAssembly;
use App\Models\Driver;
use App\Models\Facility;
use App\Models\Fleet;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Provider;
use App\Models\Role;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoFlowSeeder extends Seeder
{
    public const EMAIL = 'kankamthomas6@gmail.com';

    public const PASSWORD = 'Passw0rd@12345';

    public const PHONE = '233556906969';

    public function run(): void
    {
        $admin = Admin::query()->where('email', self::EMAIL)->first();
        if (! $admin) {
            $this->command?->warn('DemoFlowSeeder: run CreateSuperAdministrator first.');

            return;
        }

        $district = DistrictAssembly::query()->firstOrNew(['email' => self::EMAIL]);
        $district->fill([
            'district_assembly_slug' => $district->district_assembly_slug ?: 'da-demo-accra',
            'parent_slug' => null,
            'is_main' => true,
            'admin_slug' => $admin->admin_slug,
            'region' => 'Greater Accra',
            'district' => 'Accra Metropolitan',
            'first_name' => 'Demo',
            'last_name' => 'District Assembly',
            'phone_number' => self::PHONE,
            'password' => self::PASSWORD,
            'gps_address' => 'Accra, Ghana',
            'status' => 'active',
        ]);
        $district->save();
        $this->syncRole($district, 'district_assembly', 'District Assembly Admin');

        $zone = Zone::query()->firstOrCreate(
            ['name' => 'Accra Central Zone'],
            [
                'region' => 'Greater Accra',
                'description' => 'Demo zone for Accra central areas',
                'locations' => json_encode(['Osu', 'Labone', 'Cantonments']),
                'status' => 'active',
                'district_assembly' => $district->district_assembly_slug,
                'admin_slug' => $admin->admin_slug,
            ]
        );

        $provider = Provider::query()->firstOrNew(['email' => self::EMAIL]);
        $provider->fill([
            'provider_slug' => $provider->provider_slug ?: 'provider-demo-main',
            'parent_slug' => null,
            'is_main' => true,
            'first_name' => 'Demo',
            'last_name' => 'Provider',
            'business_name' => 'CleanCity Waste Services',
            'district_assembly' => $district->district_assembly_slug,
            'business_registration_number' => 'BN-DEMO-001',
            'gps_address' => 'Accra, Ghana',
            'phone_number' => self::PHONE,
            'password' => self::PASSWORD,
            'email_verified_at' => now(),
            'status' => 'active',
            'region' => 'Greater Accra',
            'location' => 'Accra',
            'registration_fee' => 0.10,
        ]);
        $provider->save();
        $this->syncRole($provider, 'provider', 'Provider Administrator');

        DB::table('provider_zones')->updateOrInsert(
            ['provider_slug' => $provider->provider_slug, 'zone_id' => $zone->id],
            ['assigned_at' => now(), 'status' => 'active', 'updated_at' => now(), 'created_at' => now()]
        );

        $facility = Facility::query()->firstOrNew(['email' => self::EMAIL]);
        $facilitySlug = $facility->facility_slug ?? 'facility-demo-weighbridge';
        $facility->fill([
            'facility_slug' => $facilitySlug,
            'parent_slug' => null,
            'is_main' => true,
            'region' => 'Greater Accra',
            'district' => 'Accra Metropolitan',
            'name' => 'Accra Weighbridge Facility',
            'first_name' => 'Demo',
            'last_name' => 'Facility',
            'phone_number' => self::PHONE,
            'password' => self::PASSWORD,
            'gps_address' => 'Accra, Ghana',
            'district_assembly' => $district->district_assembly_slug,
            'type' => 'weighbridge',
            'ownership' => 'public',
            'status' => 'active',
        ]);
        $facility->save();
        $this->syncRole($facility, 'facility', 'Facility Administrator');

        $group = Group::query()->firstOrCreate(
            ['group_slug' => 'group-demo-residential'],
            [
                'name' => 'Residential Clients',
                'provider_slug' => $provider->provider_slug,
                'description' => 'Default residential client group',
                'status' => 'active',
            ]
        );

        Driver::query()->firstOrCreate(
            ['driver_slug' => 'driver-demo-001'],
            [
                'provider_slug' => $provider->provider_slug,
                'first_name' => 'Kwame',
                'last_name' => 'Mensah',
                'phone_number' => '233244000001',
                'email' => 'driver.demo@waste.local',
                'password' => self::PASSWORD,
                'license_number' => 'DL-DEMO-001',
                'license_expiry_issued' => now()->addYear()->toDateString(),
                'status' => 'active',
            ]
        );

        Fleet::query()->firstOrCreate(
            ['fleet_slug' => 'fleet-demo-001'],
            [
                'provider_slug' => $provider->provider_slug,
                'vehicle_make' => 'Isuzu',
                'model' => 'NQR',
                'manufacture_year' => '2022',
                'license_plate' => 'GR-1234-22',
                'bin_capacity' => '5000kg',
                'color' => 'Green',
                'owner_first_name' => 'CleanCity',
                'owner_last_name' => 'Services',
                'owner_phone_number' => self::PHONE,
                'status' => 'active',
            ]
        );

        Client::query()->firstOrCreate(
            ['email' => self::EMAIL],
            [
                'client_slug' => 'client-demo-001',
                'provider_slug' => $provider->provider_slug,
                'first_name' => 'Demo',
                'last_name' => 'Client',
                'phone_number' => '233244000002',
                'password' => self::PASSWORD,
                'email_verified_at' => now(),
                'gps_address' => 'Osu, Accra',
                'latitude' => 5.5558,
                'longitude' => -0.1824,
                'type' => 'residential',
                'status' => 'active',
                'group_slug' => $group->group_slug,
                'registration_fee' => 0,
                'registration_status' => true,
            ]
        );

        $this->call(ProviderCatalogSeeder::class);

        $this->command?->info('DemoFlowSeeder: seeded district, zone, provider, facility, group, driver, fleet, client.');
        $this->command?->info('Login: '.self::EMAIL.' / '.self::PASSWORD.' (Admin, DistrictAssembly, Provider, Facility, Client)');
    }

    private function syncRole(object $actor, string $actorType, string $roleName): void
    {
        $role = Role::firstOrCreate(
            [
                'actor' => $actorType,
                'actor_slug' => $actor->actorSlugValue(),
                'name' => $roleName,
            ],
            ['role_slug' => (string) Str::uuid()]
        );

        $permissionIds = Permission::query()
            ->where('actor', $actorType)
            ->pluck('id')
            ->all();

        if ($permissionIds !== []) {
            $role->permissions()->sync($permissionIds);
        }

        $actor->role_slug = $role->role_slug;
        $actor->save();
    }
}
