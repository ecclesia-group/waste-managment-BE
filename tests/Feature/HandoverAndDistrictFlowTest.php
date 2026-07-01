<?php

use App\Models\DistrictAssembly;
use App\Models\Provider;
use App\Models\WasteHandoverRequest;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

it('allows zone provider to accept pending handover and blocks out-of-zone provider', function () {
    $zone = Zone::query()->create([
        'name' => 'Zone '.Str::upper(Str::random(4)),
        'region' => 'Greater Accra',
        'status' => 'active',
        'locations' => ['Accra Central'],
    ]);

    $requester = Provider::query()->create([
        'provider_slug' => 'prov-'.Str::lower(Str::random(8)),
        'first_name' => 'Requester',
        'phone_number' => '0240000001',
        'email' => 'requester+'.Str::lower(Str::random(6)).'@test.local',
        'password' => 'password',
    ]);

    $acceptor = Provider::query()->create([
        'provider_slug' => 'prov-'.Str::lower(Str::random(8)),
        'first_name' => 'Acceptor',
        'email' => 'acceptor+'.Str::lower(Str::random(6)).'@test.local',
        'password' => 'password',
    ]);

    $outsider = Provider::query()->create([
        'provider_slug' => 'prov-'.Str::lower(Str::random(8)),
        'first_name' => 'Outsider',
        'email' => 'outsider+'.Str::lower(Str::random(6)).'@test.local',
        'password' => 'password',
    ]);

    foreach ([$requester->provider_slug, $acceptor->provider_slug] as $slug) {
        DB::table('provider_zones')->insert([
            'provider_slug' => $slug,
            'zone_id' => $zone->id,
            'status' => 'active',
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $handover = WasteHandoverRequest::query()->create([
        'code' => 'HND-'.Str::upper(Str::random(6)),
        'requester_provider_slug' => $requester->provider_slug,
        'target_provider_slug' => null,
        'fleet_type' => 'medium_truck',
        'status' => 'pending',
        'fee_amount' => 250,
    ]);

    actingAs($outsider, 'provider')
        ->postJson('/api/provider/handover_requests/'.$handover->code.'/accept')
        ->assertStatus(401);

    actingAs($acceptor, 'provider')
        ->postJson('/api/provider/handover_requests/'.$handover->code.'/accept')
        ->assertOk();

    assertDatabaseHas('waste_handover_requests', [
        'id' => $handover->id,
        'status' => 'accepted',
        'target_provider_slug' => $acceptor->provider_slug,
    ]);
});

it('lists available zones for mmda zone management', function () {
    $district = DistrictAssembly::query()->create([
        'district_assembly_slug' => 'mmda-'.Str::lower(Str::random(8)),
        'region' => 'Greater Accra',
        'district' => 'Accra',
        'email' => 'mmda+'.Str::lower(Str::random(6)).'@test.local',
        'password' => 'password',
        'first_name' => 'MMDA',
    ]);

    Provider::query()->create([
        'provider_slug' => 'prov-'.Str::lower(Str::random(8)),
        'first_name' => 'Provider',
        'email' => 'provider+'.Str::lower(Str::random(6)).'@test.local',
        'password' => 'password',
        'district_assembly' => $district->district_assembly_slug,
    ]);

    Zone::query()->create([
        'name' => 'Zone '.Str::upper(Str::random(4)),
        'region' => 'Greater Accra',
        'status' => 'active',
        'locations' => ['Accra Central'],
    ]);

    actingAs($district, 'district_assembly')
        ->getJson('/api/district_assembly/zones')
        ->assertOk()
        ->assertJsonPath('data.0.region', 'Greater Accra');
});

it('allows mmda to assign and reallocate provider zones', function () {
    $district = DistrictAssembly::query()->create([
        'district_assembly_slug' => 'mmda-'.Str::lower(Str::random(8)),
        'region' => 'Greater Accra',
        'district' => 'Accra',
        'email' => 'mmda-zone+'.Str::lower(Str::random(6)).'@test.local',
        'password' => 'password',
        'first_name' => 'MMDA',
    ]);

    $provider = Provider::query()->create([
        'provider_slug' => 'prov-'.Str::lower(Str::random(8)),
        'first_name' => 'Provider',
        'email' => 'provider-zone+'.Str::lower(Str::random(6)).'@test.local',
        'password' => 'password',
        'district_assembly' => $district->district_assembly_slug,
    ]);

    $zoneA = Zone::query()->create([
        'name' => 'Zone A',
        'region' => 'Greater Accra',
        'status' => 'active',
        'locations' => ['Area A'],
    ]);

    $zoneB = Zone::query()->create([
        'name' => 'Zone B',
        'region' => 'Greater Accra',
        'status' => 'active',
        'locations' => ['Area B'],
    ]);

    actingAs($district, 'district_assembly')
        ->postJson('/api/district_assembly/providers/'.$provider->provider_slug.'/zones', [
            'zone_ids' => [$zoneA->id],
        ])
        ->assertOk();

    actingAs($district, 'district_assembly')
        ->postJson('/api/district_assembly/providers/'.$provider->provider_slug.'/zones', [
            'zone_ids' => [$zoneB->id],
            'replace' => true,
        ])
        ->assertOk();

    assertDatabaseHas('provider_zones', [
        'provider_slug' => $provider->provider_slug,
        'zone_id' => $zoneB->id,
        'status' => 'active',
    ]);

    assertDatabaseHas('provider_zones', [
        'provider_slug' => $provider->provider_slug,
        'zone_id' => $zoneA->id,
        'status' => 'revoked',
    ]);
});
