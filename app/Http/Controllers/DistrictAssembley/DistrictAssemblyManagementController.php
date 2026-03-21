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
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DistrictAssemblyManagementController extends Controller
{
    private function districtSlug(Request $request): string
    {
        /** @var DistrictAssembly $user */
        $user = $request->user();
        return (string) $user->district_assembly_slug;
    }

    public function listProviders(Request $request)
    {
        $districtSlug = $this->districtSlug($request);

        $providers = Provider::query()
            ->where('district_assembly', $districtSlug)
            ->get();

        if ($providers->isEmpty()) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "No providers found for this district assembly",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Providers retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $providers->toArray()
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

        $facilities = Facility::query()
            ->where('district_assembly', $districtSlug)
            ->get();

        if ($facilities->isEmpty()) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "No facilities found for this district assembly",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Facilities retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $facilities->toArray()
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

        $zoneSlugs = Provider::query()
            ->where('district_assembly', $districtSlug)
            ->pluck('zone_slug')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($zoneSlugs)) {
            return self::apiResponse(
                in_error: false,
                message: "Action Successful",
                reason: "No zones found for this district assembly",
                status_code: self::API_SUCCESS,
                data: []
            );
        }

        $zones = Zone::query()
            ->whereIn('zone_slug', $zoneSlugs)
            ->get();

        return self::apiResponse(
                in_error: false,
                message: "Action Successful",
                reason: "Zones retrieved successfully",
                status_code: self::API_SUCCESS,
                data: $zones->toArray()
        );
    }

    public function listComplaints(Request $request)
    {
        $districtSlug = $this->districtSlug($request);

        $providerSlugs = Provider::query()
            ->where('district_assembly', $districtSlug)
            ->pluck('provider_slug')
            ->toArray();

        if (empty($providerSlugs)) {
            return self::apiResponse(
                in_error: false,
                message: "Action Successful",
                reason: "No complaints found for this district assembly",
                status_code: self::API_SUCCESS,
                data: []
            );
        }

        $complaints = Complaint::query()
            ->whereIn('provider_slug', $providerSlugs)
            ->orderByDesc('created_at')
            ->get();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Complaints retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $complaints->toArray()
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

        $data = $request->validated();
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

        $data = static::processImage($image_fields, $data);
        $provider = Provider::create($data);

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

        $data = $request->validated();
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
}

