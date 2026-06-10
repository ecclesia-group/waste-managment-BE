<?php

use App\Models\Client;
use App\Models\Driver;
use App\Models\Provider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('allows provider sub-account to update main-provider driver location', function () {
    $mainProvider = Provider::query()->create([
        'provider_slug' => 'prov-' . Str::lower(Str::random(8)),
        'is_main' => true,
        'first_name' => 'Main',
        'email' => 'main+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
    ]);

    $subAccount = Provider::query()->create([
        'provider_slug' => 'prov-' . Str::lower(Str::random(8)),
        'parent_slug' => $mainProvider->provider_slug,
        'is_main' => false,
        'first_name' => 'Sub',
        'email' => 'sub+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
    ]);

    $driver = Driver::query()->create([
        'driver_slug' => 'drv-' . Str::lower(Str::random(8)),
        'provider_slug' => $mainProvider->provider_slug,
        'first_name' => 'Driver',
        'email' => 'driver+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
    ]);

    $response = $this
        ->actingAs($subAccount, 'provider')
        ->postJson('/api/provider/update_driver_location', [
            'driver_slug' => $driver->driver_slug,
            'latitude' => 5.6037,
            'longitude' => -0.1870,
        ]);

    $response->assertOk();
    $this->assertDatabaseHas('drivers', [
        'driver_slug' => $driver->driver_slug,
        'provider_slug' => $mainProvider->provider_slug,
    ]);
});

it('allows provider sub-account to see map data for main-provider assignments', function () {
    $mainProvider = Provider::query()->create([
        'provider_slug' => 'prov-' . Str::lower(Str::random(8)),
        'is_main' => true,
        'first_name' => 'Main',
        'email' => 'main+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
    ]);

    $subAccount = Provider::query()->create([
        'provider_slug' => 'prov-' . Str::lower(Str::random(8)),
        'parent_slug' => $mainProvider->provider_slug,
        'is_main' => false,
        'first_name' => 'Sub',
        'email' => 'sub+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
    ]);

    $client = Client::query()->create([
        'client_slug' => 'cli-' . Str::lower(Str::random(8)),
        'provider_slug' => $mainProvider->provider_slug,
        'first_name' => 'Client',
        'email' => 'client+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
        'latitude' => 5.6037,
        'longitude' => -0.1870,
    ]);

    DB::table('pickups')->insert([
        'code' => 'PK-' . Str::upper(Str::random(6)),
        'route_planner_id' => 1,
        'provider_slug' => $mainProvider->provider_slug,
        'client_slug' => $client->client_slug,
        'title' => 'Scheduled pickup',
        'category' => 'normal_pickup',
        'status' => 'scheduled',
        'scan_status' => 'unscanned',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this
        ->actingAs($subAccount, 'provider')
        ->getJson('/api/provider/map_pickup_overview');

    $response->assertOk();
    dump(['count' => DB::table('pickups')->count(), 'rows' => DB::table('pickups')->get()->toArray(), 'resp' => $response->json('data')]);
    $items = collect($response->json('data.items'));
    expect($items)->not->toBeEmpty();
    expect($items->pluck('provider_slug')->contains($mainProvider->provider_slug))->toBeTrue();
});
