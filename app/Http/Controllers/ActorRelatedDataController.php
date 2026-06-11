<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\DistrictAssembly;
use App\Models\Facility;
use App\Models\Payment;
use App\Models\Pickup;
use App\Models\Provider;
use App\Models\Violation;
use App\Models\WeighbridgeRecord;
use App\Models\Zone;
use App\Services\ZoneAssignmentService;
use App\Traits\PaginatesApiResults;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActorRelatedDataController extends Controller
{
    use PaginatesApiResults;

    public function clientPickups(Request $request, Client $client)
    {
        $client = $this->resolveClient($client);
        if (! $client) {
            return $this->notFound('Client');
        }

        $query = Pickup::query()
            ->where('client_slug', $client->client_slug)
            ->when($client->provider_slug, fn ($q) => $q->where('provider_slug', $client->provider_slug))
            ->with(['client', 'provider'])
            ->orderByDesc('created_at');

        return $this->paginatedApiResponse(
            $query->paginate($this->perPage($request)),
            'Client pickups retrieved successfully'
        );
    }

    public function clientViolations(Request $request, Client $client)
    {
        $client = $this->resolveClient($client);
        if (! $client) {
            return $this->notFound('Client');
        }

        $query = Violation::query()
            ->where('client_slug', $client->client_slug)
            ->when($client->provider_slug, fn ($q) => $q->where('provider_slug', $client->provider_slug))
            ->with(['client', 'provider'])
            ->orderByDesc('created_at');

        return $this->paginatedApiResponse(
            $query->paginate($this->perPage($request)),
            'Client violations retrieved successfully'
        );
    }

    public function clientPayments(Request $request, Client $client)
    {
        $client = $this->resolveClient($client);
        if (! $client) {
            return $this->notFound('Client');
        }

        $query = Payment::query()
            ->where('client_slug', $client->client_slug)
            ->when($client->provider_slug, fn ($q) => $q->where('provider_slug', $client->provider_slug))
            ->orderByDesc('created_at');

        return $this->paginatedApiResponse(
            $query->paginate($this->perPage($request)),
            'Client payments retrieved successfully'
        );
    }

    public function providerClients(Request $request, Provider $provider)
    {
        $query = Client::query()
            ->where('provider_slug', $provider->provider_slug)
            ->with('bin')
            ->orderByDesc('created_at');

        return $this->paginatedApiResponse(
            $query->paginate($this->perPage($request)),
            'Provider clients retrieved successfully'
        );
    }

    public function providerClient(Request $request, Provider $provider, Client $client)
    {
        $client = Client::where('client_slug', $client->client_slug)
            ->where('provider_slug', $provider->provider_slug)
            ->firstOrFail();

        return $this->apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Client retrieved successfully',
            status_code: self::API_SUCCESS,
            data: $client->load('bin')->toArray()
        );
    }

    public function providerPickups(Request $request, Provider $provider)
    {
        $query = Pickup::query()
            ->where('provider_slug', $provider->provider_slug)
            ->with(['client'])
            ->orderByDesc('created_at');

        return $this->paginatedApiResponse(
            $query->paginate($this->perPage($request)),
            'Provider pickups retrieved successfully'
        );
    }

    public function providerViolations(Request $request, Provider $provider)
    {
        $query = Violation::query()
            ->where('provider_slug', $provider->provider_slug)
            ->with(['client', 'provider'])
            ->orderByDesc('created_at');

        return $this->paginatedApiResponse(
            $query->paginate($this->perPage($request)),
            'Provider violations retrieved successfully'
        );
    }

    public function providerPayments(Request $request, Provider $provider)
    {
        $query = Payment::query()
            ->where('provider_slug', $provider->provider_slug)
            ->with(['client'])
            ->orderByDesc('created_at');

        return $this->paginatedApiResponse(
            $query->paginate($this->perPage($request)),
            'Provider payments retrieved successfully'
        );
    }

    public function providerWeighbridgeRecords(Request $request, Provider $provider)
    {
        $query = WeighbridgeRecord::query()
            ->where('provider_slug', $provider->provider_slug)
            ->with(['facility', 'provider', 'fleet'])
            ->orderBy('created_at', 'desc');

        return $this->paginatedApiResponse(
            $query->paginate($this->perPage($request)),
            'Provider weighbridge records retrieved successfully'
        );
    }

    public function facilityWeighbridgeRecords(Request $request, Facility $facility)
    {
        $query = WeighbridgeRecord::query()
            ->where('facility_slug', $facility->facility_slug)
            ->with(['provider', 'fleet'])
            ->orderBy('created_at', 'desc');

        return $this->paginatedApiResponse(
            $query->paginate($this->perPage($request)),
            'Facility weighbridge records retrieved successfully'
        );
    }

    public function districtAssemblyProviders(Request $request, DistrictAssembly $district_assembly)
    {
        $query = Provider::query()
            ->where('district_assembly', $district_assembly->district_assembly_slug)
            ->orderByDesc('created_at');

        return $this->paginatedApiResponse(
            $query->paginate($this->perPage($request)),
            'District assembly providers retrieved successfully'
        );
    }

    public function districtAssemblyFacilities(Request $request, DistrictAssembly $district_assembly)
    {
        $query = Facility::query()
            ->where('district_assembly', $district_assembly->district_assembly_slug)
            ->orderByDesc('created_at');

        return $this->paginatedApiResponse(
            $query->paginate($this->perPage($request)),
            'District assembly facilities retrieved successfully'
        );
    }

    public function districtAssemblyZones(Request $request, DistrictAssembly $district_assembly)
    {
        $query = Zone::query()
            ->where('district_assembly', $district_assembly->district_assembly_slug)
            ->orderByDesc('created_at');

        return $this->paginatedApiResponse(
            $query->paginate($this->perPage($request)),
            'District assembly zones retrieved successfully'
        );
    }

    public function zoneProviders(Request $request, Zone $zone)
    {
        $providerSlugs = app(ZoneAssignmentService::class)->providerSlugsInZone($zone->zone_slug);

        $query = Provider::query()
            ->whereIn('provider_slug', $providerSlugs)
            ->orderBy('business_name');

        return $this->paginatedApiResponse(
            $query->paginate($this->perPage($request)),
            'Zone providers retrieved successfully'
        );
    }

    public function zoneFacilities(Request $request, Zone $zone)
    {
        $facilitySlugs = app(ZoneAssignmentService::class)->facilitySlugsInZone($zone->zone_slug);

        $query = Facility::query()
            ->whereIn('facility_slug', $facilitySlugs)
            ->orderBy('name');

        return $this->paginatedApiResponse(
            $query->paginate($this->perPage($request)),
            'Zone facilities retrieved successfully'
        );
    }

    public function zoneClients(Request $request, Zone $zone)
    {
        $providerSlugs = app(ZoneAssignmentService::class)->providerSlugsInZone($zone->zone_slug);

        $query = Client::query()
            ->whereIn('provider_slug', $providerSlugs)
            ->with('bin')
            ->orderByDesc('created_at');

        return $this->paginatedApiResponse(
            $query->paginate($this->perPage($request)),
            'Zone clients retrieved successfully'
        );
    }

    public function zonePickups(Request $request, Zone $zone)
    {
        $providerSlugs = app(ZoneAssignmentService::class)->providerSlugsInZone($zone->zone_slug);

        $query = Pickup::query()
            ->whereIn('provider_slug', $providerSlugs)
            ->with(['client'])
            ->orderByDesc('created_at');

        return $this->paginatedApiResponse(
            $query->paginate($this->perPage($request)),
            'Zone pickups retrieved successfully'
        );
    }

    private function resolveClient(Client $client): ?Client
    {
        $providerUser = Auth::guard('provider')->user();
        $clientUser = Auth::guard('client')->user();

        $query = Client::query()->where('client_slug', $client->client_slug);

        if ($providerUser) {
            $query->where('provider_slug', $providerUser->provider_slug);
        } elseif ($clientUser) {
            $query->where('client_slug', $clientUser->client_slug);
        }

        return $query->first();
    }

    private function notFound(string $actor)
    {
        return self::apiResponse(
            in_error: true,
            message: 'Action Failed',
            reason: "{$actor} not found",
            status_code: self::API_NOT_FOUND,
            data: []
        );
    }
}
