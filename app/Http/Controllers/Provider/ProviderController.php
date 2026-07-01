<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\ProviderStatusRequest;
use App\Http\Requests\Provider\StoreProviderRegisterRequest;
use App\Http\Requests\Provider\UpdateProviderProfileRequest;
use App\Models\Notification;
use App\Models\Provider;
use App\Services\ZoneAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Symfony\Component\Clock\now;

class ProviderController extends Controller
{
    public function allProviders()
    {
        return $this->paginatedApiResponse(
            Provider::query()
                ->where('status', 'active')
                ->latest()
                ->paginate($this->perPage(request())),
            'All providers retrieved successfully'
        );
    }

    public function register(StoreProviderRegisterRequest $request)
    {
        $password = Str::random(8);
        $data = static::formatPhoneNumbersInData($request->validated());

        $zoneIds = is_array($data['zone_ids'] ?? null) ? $data['zone_ids'] : [];
        unset($data['zone_ids']);
        $zoneSlugs = array_values(array_unique(array_filter($zoneSlugs)));

        $data['provider_slug'] = Str::uuid();
        $data['password'] = $password;
        $data['email_verified_at'] = now();

        // get all images and check for bases 64 or url business_certificate_image, district_assembly_contract_image, tax_certificate_image, epa_permit_image, profile_image
        $image_fields = [
            'business_certificate_image',
            'district_assembly_contract_image',
            'tax_certificate_image',
            'epa_permit_image',
            'profile_image',
        ];

        $data = static::processImage($image_fields, $data);

        DB::beginTransaction();
        try {
            $provider = Provider::create($data);
            if ($zoneIds !== []) {
                app(ZoneAssignmentService::class)->assignZonesToProvider($provider->provider_slug, $zoneIds);
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Failed to register provider: " . $e->getMessage(),
                status_code: self::API_FAIL,
                data: []
            );
        }

        self::sendEmail(
            $provider->email,
            email_class: "App\Mail\ActorAccountCreationMail",
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
            reason: "Provider registered successfully",
            status_code: self::API_SUCCESS,
            data: array_merge($provider->load('zones', 'mmda')->toArray())
        );
    }

    public function index(Request $request)
    {
        return $this->paginatedApiResponse(
            Provider::query()
                ->orderByDesc('created_at')
                ->with('zones', 'mmda')
                ->paginate($this->perPage($request)),
            'Providers retrieved successfully'
        );
    }

    public function show(Provider $provider)
    {
        $provider = Provider::query()
            ->where('provider_slug', $provider->provider_slug)
            ->with('zones', 'mmda')
            ->first();

        if (! $provider) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Provider not found",
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

    public function updateStatus(ProviderStatusRequest $request)
    {
        $data             = $request->validated();
        $provider         = Provider::where('provider_slug', $data['provider_slug'])->first();
        $provider->status = $data['status'];

        if (($data['status'] ?? 'active') !== 'active') {
            $provider->suspension_reason = $data['suspension_reason'] ?? $provider->suspension_reason;
            $provider->corrective_action = $data['corrective_action'] ?? $provider->corrective_action;
            $provider->suspended_at = now();

            Notification::create([
                'actor' => 'provider',
                'actor_id' => (string) $provider->id,
                'actor_slug' => $provider->provider_slug,
                'title' => 'Account suspended',
                'message' => trim(
                    'Your account has been suspended.'
                        . ($provider->suspension_reason ? ' Reason: ' . $provider->suspension_reason . '.' : '')
                        . ($provider->corrective_action ? ' Corrective action: ' . $provider->corrective_action . '.' : '')
                ),
                'type' => 'account_suspension',
            ]);
        } else {
            $provider->suspension_reason = null;
            $provider->corrective_action = null;
            $provider->suspended_at = null;

            Notification::create([
                'actor' => 'provider',
                'actor_id' => (string) $provider->id,
                'actor_slug' => $provider->provider_slug,
                'title' => 'Account reactivated',
                'message' => 'Your provider account is active again.',
                'type' => 'account_reactivation',
            ]);
        }
        $provider->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Provider status updated successfully",
            status_code: self::API_SUCCESS,
            data: $provider->load('zones', 'mmda')->toArray()
        );
    }

    public function updateProfile(UpdateProviderProfileRequest $request)
    {
        $data = static::formatPhoneNumbersInData($request->validated());

        $image_fields = [
            'business_certificate_image',
            'district_assembly_contract_image',
            'tax_certificate_image',
            'epa_permit_image',
            'profile_image',
        ];

        $data = static::processImage($image_fields, $data);
        request()->user()->update($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Provider details updated successfully",
            status_code: self::API_SUCCESS,
            data: request()->user()->load('zones', 'mmda')->toArray()
        );
    }

    public function updateProviderProfile(UpdateProviderProfileRequest $request, Provider $provider)
    {
        $data = static::formatPhoneNumbersInData($request->validated());

        $zones = array_values(array_unique(array_filter($data['zone_ids'] ?? [])));
        unset($data['zone_ids']);

        app(ZoneAssignmentService::class)->setProviderZones($provider->provider_slug, $zones, true);

        $image_fields = [
            'business_certificate_image',
            'district_assembly_contract_image',
            'tax_certificate_image',
            'epa_permit_image',
            'profile_image',
        ];

        $data = static::processImage($image_fields, $data);
        $provider->update($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Provider details updated successfully",
            status_code: self::API_SUCCESS,
            data: $provider->fresh()->load('zones', 'mmda')->toArray()
        );
    }

    public function deleteProvider(Provider $provider)
    {
        $provider->delete();
        app(ZoneAssignmentService::class)->syncProviderZones($provider->provider_slug, []);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Provider deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }

    public function reassignZones(Request $request, Provider $provider)
    {
        $data = $request->validate([
            'zone_ids' => 'required|array|min:1',
            'zone_ids.*' => 'required|integer|exists:zones,id',
            'replace' => 'sometimes|boolean',
        ]);

        $zones = array_values(array_unique($data['zone_ids']));

        app(ZoneAssignmentService::class)->setProviderZones(
            $provider->provider_slug,
            $zones,
            (bool) ($data['replace'] ?? true)
        );

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Provider zones reassigned successfully',
            status_code: self::API_SUCCESS,
            data: $provider->fresh()->load('zones', 'mmda')->toArray()
        );
    }

    public function reassignMmda(Request $request, Provider $provider)
    {
        $data = $request->validate([
            'district_assembly_slug' => 'required|string|exists:district_assemblies,district_assembly_slug',
        ]);

        $provider->district_assembly = $data['district_assembly_slug'];
        $provider->save();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Provider MMDA reassigned successfully',
            status_code: self::API_SUCCESS,
            data: $provider->fresh()->load('zones', 'mmda')->toArray()
        );
    }
}
