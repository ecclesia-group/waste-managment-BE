<?php

use App\Models\Facility;
use App\Models\Provider;
use App\Models\WeighbridgeRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('supports provider to facility pending-payment weighbridge flow', function () {
    $district = 'MMDA-001';

    $provider = Provider::query()->create([
        'provider_slug' => 'prov-' . Str::lower(Str::random(8)),
        'first_name' => 'Provider',
        'email' => 'provider+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
        'district_assembly' => $district,
    ]);

    $facility = Facility::query()->create([
        'facility_slug' => 'fac-' . Str::lower(Str::random(8)),
        'region' => 'Greater Accra',
        'email' => 'facility+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
        'district_assembly' => $district,
    ]);

    $createResponse = $this
        ->actingAs($provider, 'provider')
        ->postJson('/api/provider/weighbridge_records', [
            'facility_slug' => $facility->facility_slug,
            'amount' => 150.00,
            'gross_weight' => 1250.25,
            'notes' => 'Drop-off request',
        ]);

    $createResponse->assertStatus(201);
    $code = $createResponse->json('data.code');
    expect($code)->not->toBeEmpty();

    $this->assertDatabaseHas('weighbridge_records', [
        'code' => $code,
        'provider_slug' => $provider->provider_slug,
        'facility_slug' => $facility->facility_slug,
        'payment_status' => 'pending_payment',
        'scan_status' => 'handover',
    ]);

    $verifyResponse = $this
        ->actingAs($facility, 'facility')
        ->postJson('/api/facility/verify_weigh_bridge_ticket', [
            'code' => $code,
            'payment_status' => 'credit',
            'notes' => 'Captured at facility gate',
        ]);

    $verifyResponse->assertOk();

    $this->assertDatabaseHas('weighbridge_records', [
        'code' => $code,
        'payment_status' => 'credit',
        'scan_status' => 'scanned',
    ]);
});

it('prevents provider from viewing another provider weighbridge ticket', function () {
    $providerA = Provider::query()->create([
        'provider_slug' => 'prov-' . Str::lower(Str::random(8)),
        'first_name' => 'Provider A',
        'email' => 'provider-a+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
    ]);

    $providerB = Provider::query()->create([
        'provider_slug' => 'prov-' . Str::lower(Str::random(8)),
        'first_name' => 'Provider B',
        'email' => 'provider-b+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
    ]);

    $record = WeighbridgeRecord::query()->create([
        'code' => 'WB-' . Str::upper(Str::random(8)),
        'provider_slug' => $providerA->provider_slug,
        'payment_status' => 'pending_payment',
        'scan_status' => 'handover',
        'amount' => 90,
    ]);

    $this
        ->actingAs($providerB, 'provider')
        ->getJson('/api/provider/get_single_weighbridge_record/' . $record->code)
        ->assertStatus(404);
});
