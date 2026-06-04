<?php

use App\Models\DistrictAssembly;
use App\Models\Provider;
use App\Models\WasteHandoverRequest;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('allows zone provider to accept pending handover and blocks out-of-zone provider', function () {
    $zoneSlug = 'zone-'.Str::lower(Str::random(8));

    $requester = Provider::query()->create([
        'provider_slug' => 'prov-'.Str::lower(Str::random(8)),
        'first_name' => 'Requester',
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
            'zone_slug' => $zoneSlug,
            'status' => 'active',
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $handover = WasteHandoverRequest::query()->create([
        'code' => 'HND-'.Str::upper(Str::random(6)),
        'requester_provider_slug' => $requester->provider_slug,
        'requester_type' => 'aboboya',
        'requester_name' => 'Kofi Aboboya',
        'requester_phone' => '0240000001',
        'requester_email' => 'kofi@example.com',
        'target_provider_slug' => null,
        'zone_slug' => $zoneSlug,
        'zone_slugs' => [$zoneSlug],
        'title' => 'Waste transfer',
        'status' => 'pending',
        'fee_amount' => 0,
    ]);

    $this->actingAs($outsider, 'provider')
        ->postJson('/api/provider/handover_requests/'.$handover->id.'/accept')
        ->assertStatus(401);

    $this->actingAs($acceptor, 'provider')
        ->postJson('/api/provider/handover_requests/'.$handover->id.'/accept')
        ->assertOk();

    $this->assertDatabaseHas('waste_handover_requests', [
        'id' => $handover->id,
        'status' => 'accepted',
        'target_provider_slug' => $acceptor->provider_slug,
    ]);
});

it('lists district zones via provider-zone assignments', function () {
    $district = DistrictAssembly::query()->create([
        'district_assembly_slug' => 'mmda-'.Str::lower(Str::random(8)),
        'region' => 'Greater Accra',
        'district' => 'Accra',
        'email' => 'mmda+'.Str::lower(Str::random(6)).'@test.local',
        'password' => 'password',
        'first_name' => 'MMDA',
    ]);

    $provider = Provider::query()->create([
        'provider_slug' => 'prov-'.Str::lower(Str::random(8)),
        'first_name' => 'Provider',
        'email' => 'provider+'.Str::lower(Str::random(6)).'@test.local',
        'password' => 'password',
        'district_assembly' => $district->district_assembly_slug,
    ]);

    $zone = Zone::query()->create([
        'name' => 'Zone '.Str::upper(Str::random(4)),
        'zone_slug' => 'zone-'.Str::lower(Str::random(8)),
        'region' => 'Greater Accra',
    ]);

    DB::table('provider_zones')->insert([
        'provider_slug' => $provider->provider_slug,
        'zone_slug' => $zone->zone_slug,
        'status' => 'active',
        'assigned_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($district, 'district_assembly')
        ->getJson('/api/district_assembly/zones')
        ->assertOk();
});
