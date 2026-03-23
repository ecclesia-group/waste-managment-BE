<?php
namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\ProviderStatusRequest;
use App\Http\Requests\Provider\StoreProviderRegisterRequest;
use App\Http\Requests\Provider\UpdateProviderProfileRequest;
use App\Models\Client;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Pickup;
use App\Models\Provider;
use App\Models\Violation;
use App\Models\WeighbridgeRecord;
use function Symfony\Component\Clock\now;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProviderController extends Controller
{
    public function register(StoreProviderRegisterRequest $request)
    {
        $password = Str::random(8);
        $data = $request->validated();

        $zoneSlugs = is_array($data['zone_slugs'] ?? null) ? $data['zone_slugs'] : [];
        unset($data['zone_slugs']);
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

            // Multi-zone assignment (single or multiple zone_slugs).
            foreach ($zoneSlugs as $zoneSlug) {
                DB::table('provider_zone_assignments')->updateOrInsert(
                    ['provider_slug' => $provider->provider_slug, 'zone_slug' => $zoneSlug],
                    [
                        'assigned_at' => now(),
                        'status' => 'active',
                        'updated_at' => now(),
                        // created_at is auto-managed only for Eloquent models, so set it here for first insert.
                        'created_at' => now(),
                    ]
                );
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
            data: $provider->toArray()
        );
    }

    public function index()
    {
        $provider = Provider::all();
        if ($provider->isEmpty()) {
            return self::apiResponse(
                in_error: true,
                message: "No Providers Found",
                reason: "No providers are registered in the system",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Providers retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $provider->toArray()
        );
    }

    public function show(Provider $provider)
    {
        $provider = Provider::where('provider_slug', $provider->provider_slug)->first();

        if (! $provider) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Provider not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        // Super Admin profile composition:
        // include pickups, customers, weighbridge logs, and customer-level violations/payments.
        if (Auth::guard('admin')->check()) {
            $providerSlug = $provider->provider_slug;

            $customers = Client::query()
                ->where('provider_slug', $providerSlug)
                ->orderByDesc('created_at')
                ->get();

            $customerPayload = $customers->map(function (Client $client) use ($providerSlug) {
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
            })->values();

            $zoneAssignments = DB::table('provider_zone_assignments')
                ->join('zones', 'zones.zone_slug', '=', 'provider_zone_assignments.zone_slug')
                ->where('provider_zone_assignments.provider_slug', $providerSlug)
                ->select(
                    'provider_zone_assignments.zone_slug',
                    'provider_zone_assignments.assigned_at',
                    'provider_zone_assignments.status',
                    'zones.name as zone_name',
                    'zones.region as zone_region',
                    'zones.description as zone_description',
                    'zones.locations as zone_locations'
                )
                ->orderByDesc('provider_zone_assignments.assigned_at')
                ->get()
                ->toArray();

            $payload = array_merge($provider->toArray(), [
                'zone_assignments' => $zoneAssignments,
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
                'customers' => $customerPayload->toArray(),
            ]);

            return self::apiResponse(
                in_error: false,
                message: "Action Successful",
                reason: "Provider details retrieved successfully",
                status_code: self::API_SUCCESS,
                data: $payload
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
            data: $provider->toArray()
        );
    }

    public function updateProfile(UpdateProviderProfileRequest $request)
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
            reason: "Provider details updated successfully",
            status_code: self::API_SUCCESS,
            data: request()->user()->toArray()
        );
    }

    public function updateProviderProfile(UpdateProviderProfileRequest $request, Provider $provider)
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
        $provider->update($data);
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Provider details updated successfully",
            status_code: self::API_SUCCESS,
            data: $provider->toArray()
        );
    }

    public function deleteProvider(Provider $provider)
    {
        $provider->delete();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Provider deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }
}
