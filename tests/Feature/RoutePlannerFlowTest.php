<?php

use App\Models\BulkWasteRequest;
use App\Models\Client;
use App\Models\Driver;
use App\Models\Fleet;
use App\Models\Group;
use App\Models\Provider;
use App\Models\RoutePlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

it('creates normal pickup plan using multiple groups and filters map by plan', function () {
    $provider = Provider::query()->create([
        'provider_slug' => 'prov-' . Str::lower(Str::random(8)),
        'first_name' => 'Provider',
        'email' => 'provider+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
    ]);

    $driver = Driver::query()->create([
        'driver_slug' => 'drv-' . Str::lower(Str::random(8)),
        'provider_slug' => $provider->provider_slug,
        'first_name' => 'Driver',
        'email' => 'driver+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
    ]);

    $fleet = Fleet::query()->create([
        'fleet_slug' => 'flt-' . Str::lower(Str::random(8)),
        'provider_slug' => $provider->provider_slug,
        'status' => 'active',
    ]);

    $groupA = Group::query()->create([
        'name' => 'Group A ' . Str::upper(Str::random(4)),
        'group_slug' => 'grp-' . Str::lower(Str::random(8)),
        'provider_slug' => $provider->provider_slug,
    ]);
    $groupB = Group::query()->create([
        'name' => 'Group B ' . Str::upper(Str::random(4)),
        'group_slug' => 'grp-' . Str::lower(Str::random(8)),
        'provider_slug' => $provider->provider_slug,
    ]);

    Client::query()->create([
        'client_slug' => 'cli-' . Str::lower(Str::random(8)),
        'provider_slug' => $provider->provider_slug,
        'first_name' => 'Client One',
        'email' => 'client1+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
        'group_slug' => $groupA->group_slug,
        'latitude' => 5.6037000,
        'longitude' => -0.1870000,
    ]);
    Client::query()->create([
        'client_slug' => 'cli-' . Str::lower(Str::random(8)),
        'provider_slug' => $provider->provider_slug,
        'first_name' => 'Client Two',
        'email' => 'client2+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
        'group_slug' => $groupB->group_slug,
        'latitude' => 5.6120000,
        'longitude' => -0.2010000,
    ]);

    $create = actingAs($provider, 'provider')->postJson('/api/provider/create_plan', [
        'provider_slug' => $provider->provider_slug,
        'driver_slug' => $driver->driver_slug,
        'fleet_slug' => $fleet->fleet_slug,
        'pickup_type' => 'normal',
        'pickup_date' => now()->addDay()->toDateString(),
        'group_slugs' => [$groupA->group_slug, $groupB->group_slug],
    ]);

    $create->assertOk();
    $planId = (int) $create->json('data.data.id');
    expect($planId)->toBeGreaterThan(0);
    expect($create->json('data.data'))->not->toHaveKey('pickups');
    expect($create->json('data.data.summary.total'))->toBe(2);
    expect($create->json('data.data.groups'))->not->toBeEmpty();

    assertDatabaseHas('route_planners', [
        'id' => $planId,
        'provider_slug' => $provider->provider_slug,
        'status' => 'scheduled',
    ]);

    $pickups = actingAs($provider, 'provider')->getJson("/api/provider/get_single_plan/{$planId}/pickups");
    $pickups->assertOk();
    expect(collect($pickups->json('data.data.items')))->toHaveCount(2);
    expect($pickups->json('data.data.pagination.total'))->toBe(2);
    expect(collect($pickups->json('data.data.items'))->every(fn ($p) => (int) ($p['route_planner_id'] ?? 0) === $planId))->toBeTrue();
    expect(collect($pickups->json('data.data.items'))->every(fn ($p) => isset($p['client']['latitude'], $p['client']['longitude'])))->toBeTrue();
    expect(collect($pickups->json('data.data.items'))->every(fn ($p) => ! array_key_exists('map_ready', $p['client'] ?? [])))->toBeTrue();

    $list = actingAs($provider, 'provider')->getJson('/api/provider/all_plans');
    $list->assertOk();

    $assignments = collect($list->json('data.data.assignments'));
    expect($assignments)->not->toBeEmpty();

    $assignment = $assignments->firstWhere('id', $planId);
    expect($assignment)->not->toBeNull();
    expect($assignment['pickup_type'])->toBe('normal');
    expect($assignment['groups'])->not->toBeEmpty();
    expect($assignment)->not->toHaveKey('pickups');

    assertDatabaseHas('pickups', [
        'route_planner_id' => $planId,
        'scan_status' => 'unscanned',
    ]);
});

it('rejects mixing group_slugs with bulk_waste_request pickup type', function () {
    $provider = Provider::query()->create([
        'provider_slug' => 'prov-' . Str::lower(Str::random(8)),
        'first_name' => 'Provider',
        'email' => 'provider+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
    ]);

    $driver = Driver::query()->create([
        'driver_slug' => 'drv-' . Str::lower(Str::random(8)),
        'provider_slug' => $provider->provider_slug,
        'first_name' => 'Driver',
        'email' => 'driver+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
    ]);

    $fleet = Fleet::query()->create([
        'fleet_slug' => 'flt-' . Str::lower(Str::random(8)),
        'provider_slug' => $provider->provider_slug,
        'status' => 'active',
    ]);

    $group = Group::query()->create([
        'name' => 'Group ' . Str::upper(Str::random(4)),
        'group_slug' => 'grp-' . Str::lower(Str::random(8)),
        'provider_slug' => $provider->provider_slug,
    ]);

    $response = actingAs($provider, 'provider')->postJson('/api/provider/create_plan', [
        'driver_slug' => $driver->driver_slug,
        'fleet_slug' => $fleet->fleet_slug,
        'pickup_type' => 'bulk_waste_request',
        'group_slugs' => [$group->group_slug],
        'bulk_request_codes' => ['BWR-INVALID'],
    ]);

    $response->assertStatus(422);
});

it('creates bulk waste pickup plan from selected request codes', function () {
    $provider = Provider::query()->create([
        'provider_slug' => 'prov-' . Str::lower(Str::random(8)),
        'first_name' => 'Provider',
        'email' => 'provider+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
    ]);

    $driver = Driver::query()->create([
        'driver_slug' => 'drv-' . Str::lower(Str::random(8)),
        'provider_slug' => $provider->provider_slug,
        'first_name' => 'Driver',
        'email' => 'driver+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
    ]);

    $fleet = Fleet::query()->create([
        'fleet_slug' => 'flt-' . Str::lower(Str::random(8)),
        'provider_slug' => $provider->provider_slug,
        'status' => 'active',
    ]);

    $client = Client::query()->create([
        'client_slug' => 'cli-' . Str::lower(Str::random(8)),
        'provider_slug' => $provider->provider_slug,
        'first_name' => 'Bulk Client',
        'email' => 'bulkclient+' . Str::lower(Str::random(6)) . '@test.local',
        'password' => 'password',
        'latitude' => 5.6037000,
        'longitude' => -0.1870000,
    ]);

    $bulkCode = 'BWR-' . Str::upper(Str::random(6));
    BulkWasteRequest::query()->create([
        'request_code' => $bulkCode,
        'client_slug' => $client->client_slug,
        'provider_slug' => $provider->provider_slug,
        'title' => 'Bulk Waste',
        'category' => 'bulk',
        'pickup_date' => now()->addDay(),
        'status' => 'approved',
    ]);

    $create = actingAs($provider, 'provider')->postJson('/api/provider/create_plan', [
        'provider_slug' => $provider->provider_slug,
        'driver_slug' => $driver->driver_slug,
        'fleet_slug' => $fleet->fleet_slug,
        'pickup_type' => 'bulk_waste_request',
        'pickup_date' => now()->addDay()->toDateString(),
        'bulk_request_codes' => [$bulkCode],
    ]);

    $create->assertOk();
    $plan = RoutePlanner::query()->latest('id')->first();
    expect($plan)->not->toBeNull();
    expect($plan->pickup_type)->toBe('bulk_waste_request');
    expect($plan->selectedBulkRequestCodes())->toBe([$bulkCode]);

    $summary = $create->json('data.data');
    expect($summary['pickup_type'])->toBe('bulk_waste_request');
    expect($summary['bulk_request_codes'])->toBe([$bulkCode]);
    expect($summary['bulk_waste_requests'])->toHaveCount(1);
    expect($summary['bulk_waste_requests'][0]['request_code'])->toBe($bulkCode);
    expect($summary['bulk_waste_requests'][0]['client']['client_slug'])->toBe($client->client_slug);
    expect($summary['groups'])->toBe([]);

    assertDatabaseHas('pickups', [
        'provider_slug' => $provider->provider_slug,
        'client_slug' => $client->client_slug,
        'bulk_waste_request_code' => $bulkCode,
        'route_planner_id' => $plan->id,
        'scan_status' => 'unscanned',
    ]);
    assertDatabaseHas('route_planners', [
        'id' => $plan->id,
        'status' => 'scheduled',
    ]);
});
