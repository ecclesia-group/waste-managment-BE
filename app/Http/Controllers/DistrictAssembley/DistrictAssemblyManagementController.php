<?php

namespace App\Http\Controllers\DistrictAssembley;

use App\Http\Controllers\Controller;
use App\Http\Requests\Complaint\ComplaintUpdateRequest;
use App\Http\Requests\Facility\FacilityOnboardingRequest;
use App\Http\Requests\Provider\StoreProviderRegisterRequest;
use App\Models\Complaint;
use App\Models\DistrictAssembly;
use App\Models\Facility;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\Purchase;
use App\Models\Zone;
use App\Services\ZoneAssignmentService;
use App\Traits\RespondsWithZoneAssignments;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DistrictAssemblyManagementController extends Controller
{
    use RespondsWithZoneAssignments;
    private function districtSlug(Request $request): string
    {
        /** @var DistrictAssembly $user */
        $user = $request->user();
        return (string) $user->district_assembly_slug;
    }

    public function listProviders(Request $request)
    {
        $districtSlug = $this->districtSlug($request);

        return $this->paginatedApiResponse(
            Provider::query()
                ->where('district_assembly', $districtSlug)
                ->latest()
                ->paginate($this->perPage($request)),
            'Providers retrieved successfully'
        );
    }

    public function getProvider(Request $request, Provider $provider)
    {
        $districtSlug = $this->districtSlug($request);
        if ((string) $provider->district_assembly !== $districtSlug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Provider not found in this district assembly",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Provider details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $provider->toArray()
        );
    }

    public function listFacilities(Request $request)
    {
        $districtSlug = $this->districtSlug($request);

        return $this->paginatedApiResponse(
            Facility::query()
                ->where('district_assembly', $districtSlug)
                ->latest()
                ->paginate($this->perPage($request)),
            'Facilities retrieved successfully'
        );
    }

    public function getFacility(Request $request, Facility $facility)
    {
        $districtSlug = $this->districtSlug($request);
        if ((string) $facility->district_assembly !== $districtSlug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Facility not found in this district assembly",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Facility details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $facility->toArray()
        );
    }

    public function listZones(Request $request)
    {
        $districtSlug = $this->districtSlug($request);

        return $this->paginatedApiResponse(
            Zone::query()
                ->where('district_assembly', $districtSlug)
                ->where('status', 'active')
                ->orderBy('name')
                ->paginate($this->perPage($request)),
            'District assembly zones retrieved successfully'
        );
    }

    public function getZone(Request $request, Zone $zone)
    {
        $districtSlug = $this->districtSlug($request);
        if ((string) $zone->district_assembly !== $districtSlug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Zone not found in this district assembly",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Zone details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $zone->toArray()
        );
    }

    public function createZone(Request $request)
    {
        $districtSlug = $this->districtSlug($request);
        $data = $request->validate([
            'name' => 'required|string|unique:zones,name',
            'region' => 'required|string',
            'description' => 'nullable|string',
            'locations' => 'required|array',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $zone = Zone::create([
            ...$data,
            'status' => $data['status'] ?? 'active',
            'district_assembly' => $districtSlug,
        ]);

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Zone created successfully',
            status_code: self::API_SUCCESS,
            data: $zone->toArray()
        );
    }

    public function updateZone(Request $request, Zone $zone)
    {
        $districtSlug = $this->districtSlug($request);
        if ((string) $zone->district_assembly !== $districtSlug) {
            return $this->unauthorizedDistrictActorResponse('Zone');
        }

        $data = $request->validate([
            'name' => 'sometimes|string|unique:zones,name,' . $zone->id,
            'region' => 'sometimes|string',
            'description' => 'nullable|string',
            'locations' => 'nullable|array',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $zone->update($data);

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Zone updated successfully',
            status_code: self::API_SUCCESS,
            data: $zone->fresh()->toArray()
        );
    }

    public function listProviderZones(Request $request, Provider $provider)
    {
        if (! $this->providerBelongsToDistrict($request, $provider)) {
            return $this->unauthorizedDistrictActorResponse('Provider');
        }

        return $this->listProviderZonesResponse($provider);
    }

    public function assignProviderZones(Request $request, Provider $provider)
    {
        if (! $this->providerBelongsToDistrict($request, $provider)) {
            return $this->unauthorizedDistrictActorResponse('Provider');
        }

        return $this->assignProviderZonesResponse($request, $provider);
    }

    public function revokeProviderZone(Request $request, Provider $provider, Zone $zone)
    {
        if (! $this->providerBelongsToDistrict($request, $provider)) {
            return $this->unauthorizedDistrictActorResponse('Provider');
        }

        return $this->revokeProviderZoneResponse($provider, $zone);
    }

    public function listComplaints(Request $request)
    {
        $districtSlug = $this->districtSlug($request);

        $providerSlugs = Provider::query()
            ->where('district_assembly', $districtSlug)
            ->pluck('provider_slug')
            ->toArray();

        if (empty($providerSlugs)) {
            return $this->paginatedApiResponse(
                Complaint::query()->whereRaw('1 = 0')->paginate($this->perPage($request)),
                'No complaints found for this district assembly'
            );
        }

        return $this->paginatedApiResponse(
            Complaint::query()
                ->whereIn('provider_slug', $providerSlugs)
                ->orderByDesc('created_at')
                ->paginate($this->perPage($request)),
            'Complaints retrieved successfully'
        );
    }

    public function getComplaint(Request $request, Complaint $complaint)
    {
        $districtSlug = $this->districtSlug($request);

        $provider = $complaint->provider;
        if (! $provider || (string) $provider->district_assembly !== $districtSlug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Complaint not found in this district assembly",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Complaint details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $complaint->toArray()
        );
    }

    public function updateComplaintStatus(Request $request, Complaint $complaint)
    {
        $districtSlug = $this->districtSlug($request);

        $provider = $complaint->provider;
        if (! $provider || (string) $provider->district_assembly !== $districtSlug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $data = $request->validate([
            'status' => 'required|string|in:pending,in_progress,resolved',
        ]);

        $complaint->update(['status' => $data['status']]);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Complaint status updated successfully",
            status_code: self::API_SUCCESS,
            data: $complaint->fresh()->toArray()
        );
    }

    public function registerProvider(StoreProviderRegisterRequest $request)
    {
        $userDistrictSlug = $this->districtSlug($request);
        $password = Str::random(8);

        $data = static::formatPhoneNumbersInData($request->validated());
        $data['provider_slug'] = Str::uuid();
        $data['district_assembly'] = $userDistrictSlug;
        $data['password'] = $password;
        $data['email_verified_at'] = now();

        $image_fields = [
            'business_certificate_image',
            'district_assembly_contract_image',
            'tax_certificate_image',
            'epa_permit_image',
            'profile_image',
        ];

        $zoneIds = array_values(array_unique($data['zone_ids'] ?? []));
        unset($data['zone_ids']);

        $data = static::processImage($image_fields, $data);
        $provider = Provider::create($data);

        if ($zoneIds !== []) {
            app(ZoneAssignmentService::class)->assignZonesToProvider($provider->provider_slug, $zoneIds);
        }

        self::sendEmail(
            $provider->email,
            email_class: "App\\Mail\\ActorAccountCreationMail",
            parameters: [
                $provider->email,
                $password,
                $provider->phone_number,
                $login_url = 'https://wasteprovider.tripsecuregh.com/login',
            ]
        );

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Provider registered successfully under this district assembly",
            status_code: self::API_SUCCESS,
            data: $provider->toArray()
        );
    }

    public function registerFacility(FacilityOnboardingRequest $request)
    {
        $userDistrictSlug = $this->districtSlug($request);
        $password = Str::random(8);

        $data = static::formatPhoneNumbersInData($request->validated());
        $data['facility_slug'] = Str::uuid();
        $data['district_assembly'] = $userDistrictSlug;
        $data['password'] = $password;

        $image_fields = [
            'business_certificate_image',
            'district_assembly_contract_image',
            'tax_certificate_image',
            'epa_permit_image',
            'profile_image',
        ];

        $data = static::processImage($image_fields, $data);
        $facility = Facility::create($data);

        self::sendEmail(
            $facility->email,
            email_class: "App\\Mail\\ActorAccountCreationMail",
            parameters: [
                $facility->email,
                $password,
                $facility->phone_number,
                $login_url = "https://wastefacility.tripsecuregh.com/login",
            ]
        );

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Facility registered successfully under this district assembly",
            status_code: self::API_SUCCESS,
            data: $facility->toArray()
        );
    }

    public function updateProviderStatus(Request $request, Provider $provider)
    {
        $districtSlug = $this->districtSlug($request);
        if ((string) $provider->district_assembly !== $districtSlug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $data = $request->validate([
            'status' => 'required|string|in:pending,deactivate,active',
        ]);

        $provider->status = $data['status'];
        $provider->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Provider status updated successfully",
            status_code: self::API_SUCCESS,
            data: $provider->fresh()->toArray()
        );
    }

    public function updateFacilityStatus(Request $request, Facility $facility)
    {
        $districtSlug = $this->districtSlug($request);
        if ((string) $facility->district_assembly !== $districtSlug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $data = $request->validate([
            'status' => 'required|string|in:pending,deactivate,active',
        ]);

        $facility->status = $data['status'];
        $facility->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Facility status updated successfully",
            status_code: self::API_SUCCESS,
            data: $facility->fresh()->toArray()
        );
    }

    private function providerBelongsToDistrict(Request $request, Provider $provider): bool
    {
        return (string) $provider->district_assembly === $this->districtSlug($request);
    }

    private function facilityBelongsToDistrict(Request $request, Facility $facility): bool
    {
        return (string) $facility->district_assembly === $this->districtSlug($request);
    }

    private function unauthorizedDistrictActorResponse(string $actor)
    {
        return self::apiResponse(
            in_error: true,
            message: 'Action Failed',
            reason: "{$actor} not found in this district assembly",
            status_code: self::API_NOT_FOUND,
            data: []
        );
    }
}
