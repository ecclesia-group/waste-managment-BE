<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Bin;
use App\Models\BulkWasteRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Client;
use App\Models\Complaint;
use App\Models\DistrictAssembly;
use App\Models\Driver;
use App\Models\Facility;
use App\Models\Feedback;
use App\Models\Fleet;
use App\Models\Group;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Pickup;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RoutePlanner;
use App\Models\RoutePlannerBinAssignment;
use App\Models\Violation;
use App\Models\WasteHandoverRequest;
use App\Models\WeighbridgeRecord;
use App\Models\Zone;
use App\Services\RoutePlannerService;
use App\Services\ZoneAssignmentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public const DEMO_PASSWORD = 'Password@123';

    /** @var array<string, mixed> */
    public static array $manifest = [];

    public function run(): void
    {
        self::$manifest = [
            'password' => self::DEMO_PASSWORD,
            'generated_at' => now()->toIso8601String(),
        ];

        $zones = $this->seedZones();
        $admin = $this->seedDemoAdmin();
        $district = $this->seedDistrictAssembly();
        $provider = $this->seedProvider($district, $zones);
        $facility = $this->seedFacility($district, $zones);
        $groups = $this->seedGroups($provider);
        $product = $this->seedProduct($provider);
        $client = $this->seedClient($provider, $groups['residential'], $product);
        $commercialClient = $this->seedCommercialClient($provider, $groups['commercial']);
        $bulkClient = $this->seedBulkClient($provider);
        $driver = $this->seedDriver($provider);
        $fleet = $this->seedFleet($provider);
        $pickup = $this->seedPickup($client, $provider);
        $bulkRequest = $this->seedBulkWasteRequest($bulkClient, $provider);
        $routePlanners = $this->seedRoutePlanners($provider, $driver, $fleet, $groups, $bulkRequest);
        $handover = $this->seedWasteHandover($provider, $zones['central']);
        $weighbridge = $this->seedWeighbridgeRecord($facility, $provider, $fleet, $zones['central']);
        $complaint = $this->seedComplaint($client, $provider);
        $violation = $this->seedViolation($client, $provider);
        $feedback = $this->seedFeedback($client, $provider);
        $cart = $this->seedCart($client, $product);
        $purchase = $this->seedPurchase($client, $product);
        $payment = $this->seedPayment($client, $provider, $purchase);
        $this->seedNotification($client);

        self::$manifest['models'] = [
            'admin' => $this->actorManifest($admin, 'admin'),
            'zones' => collect($zones)->map->only(['zone_slug', 'name', 'region'])->values()->all(),
            'district_assembly' => $this->actorManifest($district, 'district_assembly'),
            'provider' => $this->actorManifest($provider, 'provider'),
            'facility' => $this->actorManifest($facility, 'facility'),
            'client' => $this->actorManifest($client, 'client'),
            'groups' => [
                'residential' => ['group_slug' => $groups['residential']->group_slug, 'name' => $groups['residential']->name],
                'commercial' => ['group_slug' => $groups['commercial']->group_slug, 'name' => $groups['commercial']->name],
            ],
            'commercial_client' => ['client_slug' => $commercialClient->client_slug, 'email' => $commercialClient->email],
            'bulk_client' => $this->actorManifest($bulkClient, 'client'),
            'product' => ['product_slug' => $product->product_slug, 'name' => $product->name],
            'driver' => ['driver_slug' => $driver->driver_slug, 'email' => $driver->email],
            'fleet' => ['fleet_slug' => $fleet->fleet_slug, 'license_plate' => $fleet->license_plate],
            'pickup' => ['code' => $pickup->code, 'client_slug' => $pickup->client_slug],
            'bulk_waste_request' => ['request_code' => $bulkRequest->request_code],
            'route_planner_normal' => [
                'id' => $routePlanners['normal']->id,
                'pickup_type' => RoutePlannerService::PICKUP_TYPE_NORMAL,
                'group_slugs' => $routePlanners['normal']->selectedGroupSlugs(),
            ],
            'route_planner_bulk' => [
                'id' => $routePlanners['bulk']->id,
                'pickup_type' => RoutePlannerService::PICKUP_TYPE_BULK,
                'bulk_request_codes' => $routePlanners['bulk']->selectedBulkRequestCodes(),
            ],
            'waste_handover_request' => ['id' => $handover->id, 'code' => $handover->code],
            'weighbridge_record' => ['code' => $weighbridge->code],
            'complaint' => ['code' => $complaint->code],
            'violation' => ['code' => $violation->code],
            'feedback' => ['code' => $feedback->code],
            'cart' => ['id' => $cart->id, 'client_slug' => $cart->client_slug],
            'purchase' => ['id' => $purchase->id],
            'payment' => ['id' => $payment->id],
        ];

        self::$manifest['route_parameters'] = [
            'client' => $client->client_slug,
            'provider' => $provider->provider_slug,
            'facility' => $facility->facility_slug,
            'district_assembly' => $district->district_assembly_slug,
            'zone' => $zones['central']->zone_slug,
            'group' => $groups['residential']->group_slug,
            'driver' => $driver->driver_slug,
            'driverSlug' => $driver->driver_slug,
            'fleet' => $fleet->fleet_slug,
            'product' => $product->product_slug,
            'product_slug' => $product->product_slug,
            'pickupCode' => $pickup->code,
            'requestCode' => $bulkRequest->request_code,
            'plan' => (string) $routePlanners['normal']->id,
            'handover' => (string) $handover->id,
            'record' => $weighbridge->code,
            'entry' => $weighbridge->code,
            'complaint' => $complaint->code,
            'violation' => $violation->code,
            'feedback' => $feedback->code,
            'purchase' => (string) $purchase->id,
            'payment' => (string) $payment->id,
            'memberSlug' => $provider->provider_slug,
            'roleSlug' => $provider->role_slug ?? '',
        ];

        $manifestPath = storage_path('app/demo-data-manifest.json');
        File::ensureDirectoryExists(dirname($manifestPath));
        File::put($manifestPath, json_encode(self::$manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->command?->info('Demo data seeded. Manifest: '.$manifestPath);
    }

    private function seedDemoAdmin(): Admin
    {
        return Admin::firstOrCreate(
            ['email' => 'demo.admin@waste.test'],
            [
                'admin_slug' => 'admin-demo-001',
                'parent_slug' => null,
                'is_main' => true,
                'first_name' => 'Demo',
                'last_name' => 'Admin',
                'phone_number' => '233201000000',
                'password' => self::DEMO_PASSWORD,
                'email_verified_at' => now(),
                'status' => 'active',
            ]
        );
    }

    /** @return array{central: Zone, east: Zone} */
    private function seedZones(): array
    {
        $central = Zone::firstOrCreate(
            ['zone_slug' => 'zone-demo-accra-central'],
            [
                'name' => 'Demo Accra Central',
                'region' => 'Greater Accra',
                'description' => 'Central business district demo zone',
                'locations' => json_encode(['Osu', 'Ring Road', 'Airport City']),
                'status' => 'active',
            ]
        );

        $east = Zone::firstOrCreate(
            ['zone_slug' => 'zone-demo-accra-east'],
            [
                'name' => 'Demo Accra East',
                'region' => 'Greater Accra',
                'description' => 'Residential east demo zone',
                'locations' => json_encode(['Madina', 'Adenta', 'Ashongman']),
                'status' => 'active',
            ]
        );

        return ['central' => $central, 'east' => $east];
    }

    private function seedDistrictAssembly(): DistrictAssembly
    {
        return DistrictAssembly::firstOrCreate(
            ['email' => 'demo.mmda@waste.test'],
            [
                'district_assembly_slug' => 'mmda-demo-accra-metro',
                'parent_slug' => null,
                'is_main' => true,
                'first_name' => 'Demo',
                'last_name' => 'MMDA Officer',
                'phone_number' => '233201000001',
                'password' => self::DEMO_PASSWORD,
                'region' => 'Greater Accra',
                'district' => 'Accra Metropolitan',
                'gps_address' => 'Accra City Hall',
                'status' => 'active',
            ]
        );
    }

    /** @param  array{central: Zone, east: Zone}  $zones */
    private function seedProvider(DistrictAssembly $district, array $zones): Provider
    {
        $provider = Provider::firstOrCreate(
            ['email' => 'demo.provider@waste.test'],
            [
                'provider_slug' => 'provider-demo-001',
                'parent_slug' => null,
                'is_main' => true,
                'first_name' => 'Demo',
                'last_name' => 'Provider',
                'business_name' => 'CleanCity Waste Services',
                'district_assembly' => $district->district_assembly_slug,
                'business_registration_number' => 'BRN-DEMO-001',
                'gps_address' => '12 Independence Ave, Accra',
                'phone_number' => '233201000002',
                'password' => self::DEMO_PASSWORD,
                'email_verified_at' => now(),
                'status' => 'active',
                'region' => 'Greater Accra',
                'location' => 'Accra',
            ]
        );

        app(ZoneAssignmentService::class)->setProviderZones(
            $provider->provider_slug,
            [$zones['central']->zone_slug, $zones['east']->zone_slug],
            true
        );

        return $provider;
    }

    private function seedFacility(DistrictAssembly $district, array $zones): Facility
    {
        $facility = Facility::firstOrCreate(
            ['email' => 'demo.facility@waste.test'],
            [
                'facility_slug' => 'facility-demo-001',
                'parent_slug' => null,
                'is_main' => true,
                'name' => 'Demo Transfer Station',
                'first_name' => 'Demo',
                'last_name' => 'Facility Manager',
                'region' => 'Greater Accra',
                'district' => 'Accra Metropolitan',
                'gps_address' => '45 Industrial Area, Accra',
                'phone_number' => '233201000003',
                'password' => self::DEMO_PASSWORD,
                'type' => 'transfer_station',
                'ownership' => 'public',
                'status' => 'active',
            ]
        );

        app(ZoneAssignmentService::class)->setFacilityZones(
            $facility->facility_slug,
            [$zones['central']->zone_slug],
            true
        );

        return $facility;
    }

    /** @return array{residential: Group, commercial: Group} */
    private function seedGroups(Provider $provider): array
    {
        $residential = Group::firstOrCreate(
            ['group_slug' => 'group-demo-residential'],
            [
                'name' => 'Demo Residential Block A',
                'provider_slug' => $provider->provider_slug,
                'description' => 'Sample residential collection group for normal pickup plans',
                'status' => 'active',
            ]
        );

        $commercial = Group::firstOrCreate(
            ['group_slug' => 'group-demo-commercial'],
            [
                'name' => 'Demo Commercial Block B',
                'provider_slug' => $provider->provider_slug,
                'description' => 'Second group for multi-group normal pickup demos',
                'status' => 'active',
            ]
        );

        return ['residential' => $residential, 'commercial' => $commercial];
    }

    private function seedCommercialClient(Provider $provider, Group $group): Client
    {
        return Client::firstOrCreate(
            ['email' => 'demo.commercial@waste.test'],
            [
                'client_slug' => 'client-demo-002',
                'provider_slug' => $provider->provider_slug,
                'first_name' => 'Demo',
                'last_name' => 'Commercial',
                'phone_number' => '233201000009',
                'password' => self::DEMO_PASSWORD,
                'email_verified_at' => now(),
                'gps_address' => 'Ring Road Central, Accra',
                'latitude' => 5.5750,
                'longitude' => -0.2050,
                'type' => 'commercial',
                'status' => 'active',
                'group_slug' => $group->group_slug,
                'registration_fee' => 0,
                'registration_status' => true,
            ]
        );
    }

    private function seedBulkClient(Provider $provider): Client
    {
        return Client::firstOrCreate(
            ['email' => 'demo.bulk.client@waste.test'],
            [
                'client_slug' => 'client-demo-bulk-001',
                'provider_slug' => $provider->provider_slug,
                'first_name' => 'Demo',
                'last_name' => 'Bulk Client',
                'phone_number' => '233201000008',
                'password' => self::DEMO_PASSWORD,
                'email_verified_at' => now(),
                'gps_address' => 'Airport City, Accra',
                'latitude' => 5.6150,
                'longitude' => -0.1700,
                'type' => 'commercial',
                'status' => 'active',
                'registration_fee' => 0,
                'registration_status' => true,
            ]
        );
    }

    private function seedProduct(Provider $provider): Product
    {
        return Product::firstOrCreate(
            ['product_slug' => 'product-demo-bin-120l'],
            [
                'provider_slug' => $provider->provider_slug,
                'name' => '120L Waste Bin',
                'category' => 'bins',
                'color' => 'green',
                'size' => '120L',
                'original_price' => 250.00,
                'discounted_price' => 220.00,
                'discount_percentage' => 12,
                'quantity' => 50,
            ]
        );
    }

    private function seedClient(Provider $provider, Group $group, Product $product): Client
    {
        $client = Client::firstOrCreate(
            ['email' => 'demo.client@waste.test'],
            [
                'client_slug' => 'client-demo-001',
                'provider_slug' => $provider->provider_slug,
                'first_name' => 'Demo',
                'last_name' => 'Client',
                'phone_number' => '233201000004',
                'password' => self::DEMO_PASSWORD,
                'email_verified_at' => now(),
                'gps_address' => '14 Labone Crescent, Accra',
                'latitude' => 5.6037,
                'longitude' => -0.1870,
                'type' => 'residential',
                'status' => 'active',
                'group_slug' => $group->group_slug,
                'registration_fee' => 50.00,
                'registration_status' => true,
            ]
        );

        $bin = Bin::firstOrCreate(
            ['bin_code' => 'BIN-DEMO-001'],
            [
                'bin_slug' => 'bin-demo-001',
                'client_slug' => $client->client_slug,
                'provider_slug' => $provider->provider_slug,
                'product_slug' => $product->product_slug,
                'source' => 'provider',
                'status' => 'active',
            ]
        );

        $client->update(['bin_slug' => $bin->bin_slug]);

        return $client->fresh();
    }

    private function seedDriver(Provider $provider): Driver
    {
        return Driver::firstOrCreate(
            ['email' => 'demo.driver@waste.test'],
            [
                'driver_slug' => 'driver-demo-001',
                'provider_slug' => $provider->provider_slug,
                'first_name' => 'Kwame',
                'last_name' => 'Mensah',
                'phone_number' => '233201000005',
                'password' => self::DEMO_PASSWORD,
                'license_number' => 'DL-DEMO-001',
                'license_expiry_issued' => now()->addYear()->toDateString(),
                'emergency_contact_name' => 'Ama Mensah',
                'emergency_phone_number' => '233201000006',
                'status' => 'active',
                'latitude' => 5.6100,
                'longitude' => -0.1800,
                'last_location_at' => now(),
            ]
        );
    }

    private function seedFleet(Provider $provider): Fleet
    {
        return Fleet::firstOrCreate(
            ['fleet_slug' => 'fleet-demo-001'],
            [
                'provider_slug' => $provider->provider_slug,
                'vehicle_make' => 'Isuzu',
                'model' => 'NQR',
                'manufacture_year' => '2022',
                'license_plate' => 'GR-DEMO-001',
                'color' => 'white',
                'owner_first_name' => 'CleanCity',
                'owner_last_name' => 'Waste Services',
                'owner_phone_number' => '233201000002',
                'insurance_policy_number' => 'INS-DEMO-001',
                'insurance_expiry_date' => now()->addYear(),
                'status' => 'active',
            ]
        );
    }

    private function seedPickup(Client $client, Provider $provider): Pickup
    {
        return Pickup::firstOrCreate(
            ['code' => 'PKP-DEMO-001'],
            [
                'client_slug' => $client->client_slug,
                'provider_slug' => $provider->provider_slug,
                'title' => 'Weekly household pickup',
                'category' => 'general',
                'description' => 'Demo scheduled pickup for frontend integration',
                'status' => 'pending',
                'scan_status' => 'unscanned',
                'location' => '14 Labone Crescent, Accra',
                'amount' => 25.00,
                'pickup_date' => now()->addDays(2),
            ]
        );
    }

    private function seedBulkWasteRequest(Client $client, Provider $provider): BulkWasteRequest
    {
        return BulkWasteRequest::firstOrCreate(
            ['request_code' => 'BWR-DEMO-001'],
            [
                'client_slug' => $client->client_slug,
                'provider_slug' => $provider->provider_slug,
                'title' => 'Office renovation debris',
                'category' => 'bulk',
                'description' => 'Demo bulk waste collection request',
                'location' => 'Airport City, Accra',
                'status' => 'approved',
                'amount' => 350.00,
                'payment_status' => 'pending',
                'pickup_date' => now()->addDays(5),
                'approved_at' => now(),
            ]
        );
    }

    /**
     * @param  array{residential: Group, commercial: Group}  $groups
     * @return array{normal: RoutePlanner, bulk: RoutePlanner}
     */
    private function seedRoutePlanners(
        Provider $provider,
        Driver $driver,
        Fleet $fleet,
        array $groups,
        BulkWasteRequest $bulkRequest
    ): array {
        $service = app(RoutePlannerService::class);
        $pickupDate = now()->addDay()->toDateString();

        RoutePlanner::query()
            ->where('provider_slug', $provider->provider_slug)
            ->whereIn('pickup_type', [RoutePlannerService::PICKUP_TYPE_NORMAL, RoutePlannerService::PICKUP_TYPE_BULK])
            ->delete();

        $bulkRequest->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $normal = $service->createPlan([
            'provider_slug' => $provider->provider_slug,
            'driver_slug' => $driver->driver_slug,
            'fleet_slug' => $fleet->fleet_slug,
            'pickup_type' => RoutePlannerService::PICKUP_TYPE_NORMAL,
            'pickup_date' => $pickupDate,
            'group_slugs' => [
                $groups['residential']->group_slug,
                $groups['commercial']->group_slug,
            ],
            'status' => 'pending',
        ]);

        $bulk = $service->createPlan([
            'provider_slug' => $provider->provider_slug,
            'driver_slug' => $driver->driver_slug,
            'fleet_slug' => $fleet->fleet_slug,
            'pickup_type' => RoutePlannerService::PICKUP_TYPE_BULK,
            'pickup_date' => $pickupDate,
            'bulk_request_codes' => [$bulkRequest->request_code],
            'status' => 'pending',
        ]);

        return ['normal' => $normal, 'bulk' => $bulk];
    }

    private function seedWasteHandover(Provider $provider, Zone $zone): WasteHandoverRequest
    {
        return WasteHandoverRequest::firstOrCreate(
            ['code' => 'HND-DEMO-001'],
            [
                'requester_provider_slug' => $provider->provider_slug,
                'requester_type' => 'aboboya',
                'requester_name' => 'Kofi Aboboya',
                'requester_phone' => '233201000007',
                'requester_email' => 'kofi.aboboya@demo.test',
                'zone_slug' => $zone->zone_slug,
                'zone_slugs' => [$zone->zone_slug],
                'title' => 'Demo handover to main provider',
                'waste_types' => ['general', 'recyclable'],
                'description' => 'Sample handover request for API docs',
                'pickup_location' => 'Kaneshie Market',
                'latitude' => 5.5900,
                'longitude' => -0.2400,
                'fee_amount' => 75.00,
                'payment_status' => 'pending',
                'status' => 'pending',
            ]
        );
    }

    private function seedWeighbridgeRecord(
        Facility $facility,
        Provider $provider,
        Fleet $fleet,
        Zone $zone
    ): WeighbridgeRecord {
        return WeighbridgeRecord::firstOrCreate(
            ['code' => 'WBR-DEMO-001'],
            [
                'facility_slug' => $facility->facility_slug,
                'provider_slug' => $provider->provider_slug,
                'fleet_slug' => $fleet->fleet_slug,
                'fleet_code' => $fleet->license_plate,
                'gross_weight' => 3250.50,
                'amount' => 180.00,
                'zone_slug' => $zone->zone_slug,
                'payment_status' => 'pending_payment',
                'scan_status' => 'unscanned',
                'notes' => 'Demo weighbridge entry',
            ]
        );
    }

    private function seedComplaint(Client $client, Provider $provider): Complaint
    {
        return Complaint::firstOrCreate(
            ['code' => 'CMP-DEMO-001'],
            [
                'client_slug' => $client->client_slug,
                'provider_slug' => $provider->provider_slug,
                'location' => 'Labone, Accra',
                'description' => 'Missed collection on scheduled day',
                'status' => 'open',
            ]
        );
    }

    private function seedViolation(Client $client, Provider $provider): Violation
    {
        return Violation::firstOrCreate(
            ['code' => 'VIO-DEMO-001'],
            [
                'client_slug' => $client->client_slug,
                'provider_slug' => $provider->provider_slug,
                'type' => 'illegal_dumping',
                'location' => 'Ring Road Central',
                'description' => 'Unauthorized waste disposal',
                'status' => 'pending',
            ]
        );
    }

    private function seedFeedback(Client $client, Provider $provider): Feedback
    {
        return Feedback::firstOrCreate(
            ['code' => 'FDB-DEMO-001'],
            [
                'client_slug' => $client->client_slug,
                'provider_slug' => $provider->provider_slug,
                'ratings' => 4,
                'comments' => 'Generally good service, occasional delays.',
                'score' => 8.5,
                'status' => 'pending',
            ]
        );
    }

    private function seedCart(Client $client, Product $product): Cart
    {
        $cart = Cart::firstOrCreate(['client_slug' => $client->client_slug]);

        CartItem::firstOrCreate(
            ['cart_id' => $cart->id, 'product_slug' => $product->product_slug],
            ['quantity' => 2]
        );

        return $cart;
    }

    private function seedPurchase(Client $client, Product $product): Purchase
    {
        $purchase = Purchase::firstOrCreate(
            ['client_slug' => $client->client_slug, 'status' => 'pending'],
            [
                'number_of_items' => 1,
                'total_price' => 440.00,
            ]
        );

        PurchaseItem::firstOrCreate(
            ['purchase_id' => (string) $purchase->id, 'product_slug' => $product->product_slug],
            [
                'name' => $product->name,
                'price' => 220.00,
                'quantity' => 2,
            ]
        );

        return $purchase;
    }

    private function seedPayment(Client $client, Provider $provider, Purchase $purchase): Payment
    {
        return Payment::firstOrCreate(
            ['transaction_id' => 'TXN-DEMO-001'],
            [
                'client_slug' => $client->client_slug,
                'provider_slug' => $provider->provider_slug,
                'payment_type' => Payment::PAYMENT_TYPE_PURCHASE,
                'payment_method' => 'momo',
                'network' => 'MTN',
                'phone_number' => '233201000004',
                'name' => 'Demo Client',
                'client_email' => 'demo.client@waste.test',
                'amount' => 440.00,
                'currency' => 'GHS',
                'status' => Payment::STATUS_PENDING,
                'purchase_id' => (string) $purchase->id,
            ]
        );
    }

    private function seedNotification(Client $client): void
    {
        Notification::firstOrCreate(
            [
                'actor' => 'client',
                'actor_slug' => $client->client_slug,
                'title' => 'Welcome to Waste Management',
            ],
            [
                'actor_id' => (string) $client->id,
                'message' => 'Your demo account is ready for frontend integration testing.',
                'type' => 'info',
                'is_read' => false,
            ]
        );
    }

    /** @return array<string, mixed> */
    private function actorManifest(object $actor, string $guard): array
    {
        $slugKey = match ($guard) {
            'admin' => 'admin_slug',
            'client' => 'client_slug',
            'provider' => 'provider_slug',
            'facility' => 'facility_slug',
            'district_assembly' => 'district_assembly_slug',
            default => 'slug',
        };

        return [
            'guard' => $guard,
            'email' => $actor->email,
            'phone_number' => $actor->phone_number ?? null,
            'slug' => $actor->{$slugKey},
            'login_endpoint' => '/api/'.$guard.'/login',
            'login_payload' => [
                'emailOrPhone' => $actor->email,
                'password' => self::DEMO_PASSWORD,
            ],
        ];
    }
}
