<?php

use App\Models\DistrictAssembly;
use App\Models\Provider;
use App\Models\WasteHandoverRequest;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('blocks unrelated provider from accepting handover request', function () {
    $requester = Provider::query()->create([
        'provider_slug' => 'prov-' . Str::lower(Str::random(8)),
        'first_name' => 'Requester',
        'email' => 'requester+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
    ]);

    $target = Provider::query()->create([
        'provider_slug' => 'prov-' . Str::lower(Str::random(8)),
        'first_name' => 'Target',
        'email' => 'target+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
    ]);

    $other = Provider::query()->create([
        'provider_slug' => 'prov-' . Str::lower(Str::random(8)),
        'first_name' => 'Other',
        'email' => 'other+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
    ]);

    $handover = WasteHandoverRequest::query()->create([
        'code' => 'HND-' . Str::upper(Str::random(6)),
        'requester_provider_slug' => $requester->provider_slug,
        'target_provider_slug' => $target->provider_slug,
        'title' => 'Waste transfer',
        'status' => 'pending',
        'fee_amount' => 0,
    ]);

    $this
        ->actingAs($other, 'provider')
        ->postJson('/api/provider/handover_requests/' . $handover->id . '/accept')
        ->assertStatus(401);

    $this->assertDatabaseHas('waste_handover_requests', [
        'id' => $handover->id,
        'status' => 'pending',
        'target_provider_slug' => $target->provider_slug,
    ]);
});

it('lists district zones via provider-zone assignments', function () {
    $district = DistrictAssembly::query()->create([
        'district_assembly_slug' => 'mmda-' . Str::lower(Str::random(8)),
        'region' => 'Greater Accra',
        'district' => 'Accra',
        'email' => 'mmda+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
        'first_name' => 'MMDA',
    ]);

    $provider = Provider::query()->create([
        'provider_slug' => 'prov-' . Str::lower(Str::random(8)),
        'first_name' => 'Provider',
        'email' => 'provider+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
        'district_assembly' => $district->district_assembly_slug,
    ]);

    $zone = Zone::query()->create([
        'name' => 'Zone ' . Str::upper(Str::random(4)),
        'zone_slug' => 'zone-' . Str::lower(Str::random(8)),
        'region' => 'Greater Accra',
        'district_assembly_slug' => $district->district_assembly_slug,
    ]);

    DB::table('provider_zone_assignments')->insert([
        'provider_slug' => $provider->provider_slug,
        'zone_slug' => $zone->zone_slug,
        'assigned_at' => now(),
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this
        ->actingAs($district, 'district_assembly')
        ->getJson('/api/district_assembly/zones');

    $response->assertOk();
    expect(collect($response->json('data'))->pluck('zone_slug')->contains($zone->zone_slug))->toBeTrue();
});
