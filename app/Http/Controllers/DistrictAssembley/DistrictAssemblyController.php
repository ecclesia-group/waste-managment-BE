<?php
namespace App\Http\Controllers\DistrictAssembley;

use App\Http\Controllers\Controller;
use App\Http\Requests\DistrictAssembley\AccountStatusRequest;
use App\Http\Requests\DistrictAssembley\OnboardingRequest;
use App\Http\Requests\DistrictAssembley\ProfileUpdateRequest;
use App\Models\Client;
use App\Models\DistrictAssembly;
use App\Models\Facility;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Pickup;
use App\Models\Provider;
use App\Models\Violation;
use App\Models\WeighbridgeRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DistrictAssemblyController extends Controller
{
    public function register(OnboardingRequest $request)
    {
        $password                       = Str::random(8);
        $data                           = $request->validated();
        $data['district_assembly_slug'] = Str::uuid();
        $data['password']               = $password;

        // get all images and check for bases 64 or url business_certificate_image, district_assembly_contract_image, tax_certificate_image, epa_permit_image, profile_image
        $image_fields = [
            'profile_image',
        ];

        $data              = static::processImage($image_fields, $data);
        $district_assembly = DistrictAssembly::create($data);

        self::sendEmail(
            $district_assembly->email,
            email_class: "App\Mail\ActorAccountCreationMail",
            parameters: [
                $district_assembly->email,
                $password,
                $district_assembly->phone_number,
                $login_url = 'https://wastemmda.tripsecuregh.com/login',
            ]
        );

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Facility registered successfully",
            status_code: self::API_SUCCESS,
            data: $district_assembly->toArray()
        );
    }

    public function index()
    {
        $district_assemblies = DistrictAssembly::all();
        if ($district_assemblies->isEmpty()) {
            return self::apiResponse(
                in_error: true,
                message: "No District Assemblies Found",
                reason: "No district assemblies are registered in the system",
                status_code: self::API_NOT_FOUND,
                data: null
            );
        }
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "District Assemblies retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $district_assemblies->toArray()
        );
    }

    public function show(DistrictAssembly $district_assembly)
    {
        if (Auth::guard('admin')->check()) {
            $districtSlug = $district_assembly->district_assembly_slug;

            $providers = Provider::query()
                ->where('district_assembly', $districtSlug)
                ->orderByDesc('created_at')
                ->get()
                ->map(function (Provider $provider) {
                    $providerSlug = $provider->provider_slug;

                    $customers = Client::query()
                        ->where('provider_slug', $providerSlug)
                        ->orderByDesc('created_at')
                        ->get()
                        ->map(function (Client $client) use ($providerSlug) {
                            return array_merge($client->toArray(), [
                                'pickups' => Pickup::query()
                                    ->where('provider_slug', $providerSlug)
                                    ->where('client_slug', $client->client_slug)
                                    ->orderByDesc('created_at')
                                    ->get()
                                    ->toArray(),
                                'violations' => Violation::query()
                                    ->where('provider_slug', $providerSlug)
                                    ->where('client_slug', $client->client_slug)
                                    ->orderByDesc('created_at')
                                    ->get()
                                    ->toArray(),
                                'payments' => Payment::query()
                                    ->where('provider_slug', $providerSlug)
                                    ->where('client_slug', $client->client_slug)
                                    ->orderByDesc('created_at')
                                    ->get()
                                    ->toArray(),
                            ]);
                        })
                        ->values()
                        ->toArray();

                    return array_merge($provider->toArray(), [
                        'pickups' => Pickup::query()
                            ->where('provider_slug', $providerSlug)
                            ->orderByDesc('created_at')
                            ->get()
                            ->toArray(),
                        'weighbridge_records' => WeighbridgeRecord::query()
                            ->where('provider_slug', $providerSlug)
                            ->orderByDesc('created_at')
                            ->get()
                            ->toArray(),
                        'customers' => $customers,
                    ]);
                })
                ->values()
                ->toArray();

            $facilities = Facility::query()
                ->where('district_assembly', $districtSlug)
                ->orderByDesc('created_at')
                ->get()
                ->map(function (Facility $facility) {
                    return array_merge($facility->toArray(), [
                        'weighbridge_records' => WeighbridgeRecord::query()
                            ->where('facility_slug', $facility->facility_slug)
                            ->orderByDesc('created_at')
                            ->get()
                            ->toArray(),
                    ]);
                })
                ->values()
                ->toArray();

            return self::apiResponse(
                in_error: false,
                message: "Action Successful",
                reason: "District Assembly details retrieved successfully",
                status_code: self::API_SUCCESS,
                data: array_merge($district_assembly->toArray(), [
                    'providers' => $providers,
                    'facilities' => $facilities,
                ])
            );
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "District Assembly details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $district_assembly->toArray()
        );
    }

    public function updateStatus(AccountStatusRequest $request)
    {
        $data                      = $request->validated();
        $district_assembly         = DistrictAssembly::where('district_assembly_slug', $data['district_assembly_slug'])->first();
        $district_assembly->status = $data['status'];

        if (($data['status'] ?? 'active') !== 'active') {
            $district_assembly->suspension_reason = $data['suspension_reason'] ?? $district_assembly->suspension_reason;
            $district_assembly->corrective_action = $data['corrective_action'] ?? $district_assembly->corrective_action;
            $district_assembly->suspended_at = now();

            Notification::create([
                'actor' => 'district_assembly',
                'actor_id' => (string) $district_assembly->id,
                'actor_slug' => $district_assembly->district_assembly_slug,
                'title' => 'Account suspended',
                'message' => trim(
                    'Your district assembly account has been suspended.'
                    . ($district_assembly->suspension_reason ? ' Reason: ' . $district_assembly->suspension_reason . '.' : '')
                    . ($district_assembly->corrective_action ? ' Corrective action: ' . $district_assembly->corrective_action . '.' : '')
                ),
                'type' => 'account_suspension',
            ]);
        } else {
            $district_assembly->suspension_reason = null;
            $district_assembly->corrective_action = null;
            $district_assembly->suspended_at = null;

            Notification::create([
                'actor' => 'district_assembly',
                'actor_id' => (string) $district_assembly->id,
                'actor_slug' => $district_assembly->district_assembly_slug,
                'title' => 'Account reactivated',
                'message' => 'Your district assembly account is active again.',
                'type' => 'account_reactivation',
            ]);
        }
        $district_assembly->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "District Assembly status updated successfully",
            status_code: self::API_SUCCESS,
            data: $district_assembly->toArray()
        );
    }

    public function updateProfile(ProfileUpdateRequest $request)
    {
        $data = $request->validated();

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
            reason: "District Assembly details updated successfully",
            status_code: self::API_SUCCESS,
            data: request()->user()->toArray()
        );
    }

    public function updateDistrictAssemblyProfile(ProfileUpdateRequest $request, DistrictAssembly $district_assembly)
    {
        $data         = $request->validated();
        $image_fields = [
            'business_certificate_image',
            'district_assembly_contract_image',
            'tax_certificate_image',
            'epa_permit_image',
            'profile_image',
        ];

        $data = static::processImage($image_fields, $data);

        $district_assembly->update($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "District Assembly details updated successfully",
            status_code: self::API_SUCCESS,
            data: $district_assembly->toArray()
        );
    }

    public function deleteDistrictAssembly(DistrictAssembly $district_assembly)
    {
        $district_assembly->delete();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "District Assembly deleted successfully",
            status_code: self::API_SUCCESS,
            data: null
        );
    }
}
