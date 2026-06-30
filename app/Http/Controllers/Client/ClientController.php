<?php
namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\RegisterRequest;
use App\Http\Requests\Client\StatusRequest;
use App\Http\Requests\Client\UpdateClientProfileRequest;
use App\Models\Bin;
use App\Models\Client;
use App\Services\ClientLocationGeocodingService;
use App\Traits\PaginatesApiResults;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    use PaginatesApiResults;
    public function register(RegisterRequest $request)
    {
        $user                      = Auth::user();
        $password                  = Str::random(8);
        $data                      = $request->validated();
        $data['client_slug']       = Str::uuid();
        $data['password']          = $password;
        $data['provider_slug']     = self::actorProviderSlug($user);
        $data['email_verified_at'] = now();

        // get all images and check for bases 64 or url business_certificate_image, district_assembly_contract_image, tax_certificate_image, epa_permit_image, profile_image
        $image_fields = [
            'profile_image',
        ];

        $data     = static::processImage($image_fields, $data);
        $data['registration_status'] = ((float) ($data['registration_fee'] ?? 0)) <= 0;

        $data = $this->applyGeocodedCoordinates($data);

        $client = Client::create(collect($data)->except('registration_status')->all());
        $this->ensureRegistrationBin($client);

        self::sendEmail(
            $client->email,
            email_class: "App\Mail\ActorAccountCreationMail",
            parameters: [
                $client->email,
                $password,
                $client->phone_number,
                $login_url = "https://wasteclient.tripsecuregh.com/login",
            ]
        );

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Client registered successfully",
            status_code: self::API_SUCCESS,
            data: $client->load('bin')->toArray()
        );
    }

    public function allClients()
    {
        $user = Auth::user();
        $ownerSlug = self::ownerProviderSlug($user);
        $clients = Client::query()
            ->forProviderOrganisation((string) $ownerSlug)
            ->with('bin')
            ->orderByDesc('created_at')
            ->paginate($this->perPage(request()));

        return $this->paginatedApiResponse($clients, 'Clients retrieved successfully');
    }

    public function show(Client $client)
    {
        $providerUser = Auth::guard('provider')->user();

        if ($providerUser) {
            $client = Client::query()
                ->where('client_slug', $client->client_slug)
                ->forProviderOrganisation((string) self::ownerProviderSlug($providerUser))
                ->first();
        } else {
            $client = Client::where('client_slug', $client->client_slug)->first();
        }

        if (! $client) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Client not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Client details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $client->load('bin')->toArray()
        );
    }

    public function updateStatus(StatusRequest $request)
    {
        $data           = $request->validated();

        $user = Auth::guard('provider')->user();
        $ownerSlug = self::ownerProviderSlug($user);

        $client = Client::query()
            ->where('client_slug', $data['client_slug'])
            ->forProviderOrganisation((string) $ownerSlug)
            ->first();

        if (! $client) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Client not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        $client->status = $data['status'];
        $client->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Client status updated successfully",
            status_code: self::API_SUCCESS,
            data: $client->load('bin')->toArray()
        );
    }

    public function updateClientProfile(UpdateClientProfileRequest $request, Client $client)
    {
        $providerUser = Auth::guard('provider')->user();
        $clientUser = Auth::guard('client')->user();

        $query = Client::query()->where('client_slug', $client->client_slug);

        if ($providerUser) {
            $query->forProviderOrganisation((string) self::ownerProviderSlug($providerUser));
        } elseif ($clientUser) {
            $query->where('client_slug', $clientUser->client_slug);
        }

        $client = $query->first();

        if (! $client) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Client not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        $data         = $request->validated();
        $image_fields = [
            'profile_image',
        ];

        $data = static::processImage($image_fields, $data);

        if ($clientUser) {
            unset($data['registration_fee'], $data['registration_status']);
        } elseif ($providerUser && array_key_exists('registration_fee', $data)) {
            $oldFee = (float) ($client->registration_fee ?? 0);
            $newFee = (float) ($data['registration_fee'] ?? 0);
            if ($newFee <= 0) {
                $data['registration_status'] = true;
            } elseif (abs($oldFee - $newFee) > 0.009) {
                $data['registration_status'] = false;
            }
        }

        $addressChanged = isset($data['gps_address'])
            && (string) $data['gps_address'] !== (string) $client->gps_address;

        if ($addressChanged || empty($client->latitude) || empty($client->longitude)) {
            $data = $this->applyGeocodedCoordinates($data, force: $addressChanged);
        }

        $client->update($data);
        $this->ensureRegistrationBin($client);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Client details updated successfully",
            status_code: self::API_SUCCESS,
            data: $client->fresh()->load('bin')->toArray()
        );
    }

    /**
     * Fill latitude/longitude from gps_address when missing (Google Maps, then Ghana Post GPS).
     */
    private function applyGeocodedCoordinates(array $data, bool $force = false): array
    {
        if (! $force && ! empty($data['latitude']) && ! empty($data['longitude'])) {
            return $data;
        }

        if (empty($data['gps_address'])) {
            return $data;
        }

        $coords = app(ClientLocationGeocodingService::class)
            ->resolveCoordinates((string) $data['gps_address']);

        if ($coords === null) {
            return $data;
        }

        $data['latitude'] = $coords['latitude'];
        $data['longitude'] = $coords['longitude'];

        return $data;
    }

    public function deleteClient(Client $client)
    {
        $user = Auth::guard('provider')->user();
        $ownerSlug = self::ownerProviderSlug($user);

        $deleted = Client::query()
            ->where('client_slug', $client->client_slug)
            ->forProviderOrganisation((string) $ownerSlug)
            ->delete();

        if ($deleted === 0) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Client not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Client deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }

    // Scan QR code to get client details (for providers)
    public function scanQRCode()
    {
        $providerUser = request()->user();

        $data = request()->validate([
            'qrcode_data' => 'required|string',
        ]);

        try {
            $qrData = json_decode($data['qrcode_data'], true);

            if (! $qrData || ! isset($qrData['client_slug']) || ! isset($qrData['bin_code'])) {
                return self::apiResponse(
                    in_error: true,
                    message: "Action Failed",
                    reason: "Invalid QR code data",
                    status_code: self::API_FAIL,
                    data: []
                );
            }

            $ownerSlug = self::ownerProviderSlug($providerUser);

            $bin = Bin::query()
                ->where('bin_code', $qrData['bin_code'])
                ->forProviderOrganisation((string) $ownerSlug)
                ->where('status', 'active')
                ->first();

            $client = Client::query()
                ->where('client_slug', $qrData['client_slug'])
                ->forProviderOrganisation((string) $ownerSlug)
                ->first();

            if (! $client) {
                return self::apiResponse(
                    in_error: true,
                    message: "Action Failed",
                    reason: "Client not found",
                    status_code: self::API_NOT_FOUND,
                    data: []
                );
            }

            // Prevent old/damaged QR codes from working after regeneration.
            if (! $bin && (string) $client->bin_code !== (string) ($qrData['bin_code'] ?? '')) {
                return self::apiResponse(
                    in_error: true,
                    message: "Action Failed",
                    reason: "QR code does not match the current bin_code",
                    status_code: self::API_FAIL,
                    data: []
                );
            }

            return self::apiResponse(
                in_error: false,
                message: "Action Successful",
                reason: "Client details retrieved successfully",
                status_code: self::API_SUCCESS,
                data: [
                    'client_slug'     => $client->client_slug,
                    'name'            => $client->first_name . ' ' . ($client->last_name ?? ''),
                    'phone_number'    => $client->phone_number,
                    'email'           => $client->email,
                    'gps_address'     => $client->gps_address,
                    'pickup_location' => $client->pickup_location,
                    'bin_code'        => $client->bin_code,
                    'bin_size'        => $client->bin_size,
                    'bin'             => $bin?->toArray(),
                ]
            );
        } catch (\Exception $e) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Failed to scan QR code: " . $e->getMessage(),
                status_code: self::API_FAIL,
                data: []
            );
        }
    }

    private function ensureRegistrationBin(Client $client): void
    {
        $existing = Bin::query()
            ->where('client_slug', $client->client_slug)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            if (empty($client->bin_slug)) {
                $client->update(['bin_slug' => $existing->bin_slug]);
            }

            return;
        }

        do {
            $binCode = 'BIN-' . Str::upper(Str::random(8));
        } while (Bin::query()->where('bin_code', $binCode)->exists());

        $binSlug = (string) Str::uuid();

        Bin::query()->create([
            'bin_slug' => $binSlug,
            'bin_code' => $binCode,
            'client_slug' => $client->client_slug,
            'provider_slug' => $client->provider_slug,
            'product_slug' => null,
            'source' => 'registration',
            'status' => 'active',
        ]);

        $client->update(['bin_slug' => $binSlug]);
    }
}
