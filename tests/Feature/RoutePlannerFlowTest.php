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
    $planId = (int) $create->json('data.data.assignment.assignment_id');
    expect($planId)->toBeGreaterThan(0);

    assertDatabaseHas('route_planners', [
        'id' => $planId,
        'provider_slug' => $provider->provider_slug,
    ]);

    $list = actingAs($provider, 'provider')->getJson('/api/provider/all_plans');
    $list->assertOk();

    $assignments = collect($list->json('data.data.assignments'));
    expect($assignments)->not->toBeEmpty();

    $assignment = $assignments->firstWhere('assignment_id', $planId);
    expect($assignment)->not->toBeNull();
    expect($assignment['pickup_type'])->toBe('normal');
    expect(collect($assignment['pickups']))->not->toBeEmpty();
    expect($assignment['selected_group_slugs'])->toContain($groupA->group_slug, $groupB->group_slug);
    expect($assignment['selection']['mode'])->toBe('normal');
    expect(collect($assignment['pickups'])->every(fn ($p) => (int) ($p['assignment_id'] ?? 0) === $planId))->toBeTrue();
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

    $assignment = $create->json('data.data.assignment');
    expect($assignment['pickup_type'])->toBe('bulk_waste_request');
    expect($assignment['selected_bulk_request_codes'])->toBe([$bulkCode]);
    expect($assignment['selection']['mode'])->toBe('bulk_waste_request');

    assertDatabaseHas('pickups', [
        'provider_slug' => $provider->provider_slug,
        'client_slug' => $client->client_slug,
        'bulk_waste_request_code' => $bulkCode,
    ]);
    assertDatabaseHas('route_planner_bin_assignments', [
        'route_planner_id' => $plan->id,
        'provider_slug' => $provider->provider_slug,
        'client_slug' => $client->client_slug,
    ]);
});
