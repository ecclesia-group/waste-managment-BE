<?php
namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\RegisterRequest;
use App\Http\Requests\Client\StatusRequest;
use App\Http\Requests\Client\UpdateClientProfileRequest;
use App\Models\Bin;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Pickup;
use App\Models\Violation;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user                      = Auth::user();
        $password                  = Str::random(8);
        $data                      = $request->validated();
        $data['client_slug']       = Str::uuid();
        $data['password']          = $password;
        $data['provider_slug']     = $user->provider_slug;
        $data['email_verified_at'] = now();

        // get all images and check for bases 64 or url business_certificate_image, district_assembly_contract_image, tax_certificate_image, epa_permit_image, profile_image
        $image_fields = [
            'qrcode',
            'profile_image',
        ];

        $data     = static::processImage($image_fields, $data);
        $data['registration_status'] = ((float) ($data['registration_fee'] ?? 0)) <= 0;

        if (empty($data['group_slug'])) {
            $firstGroup = \Illuminate\Support\Arr::first($data['group_slugs'] ?? []);
            if ($firstGroup) {
                $data['group_slug'] = $firstGroup;
            }
        }

        $client = Client::create(collect($data)->except('group_slugs')->all());
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
            data: $client->load('group', 'bins')->toArray()
        );
    }

    public function allClients()
    {
        $user    = Auth::user();
        $clients = Client::where('provider_slug', $user->provider_slug)
            ->with('group', 'bins')
            ->get();
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Clients retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $clients->toArray()
        );
    }

    public function show(Client $client)
    {
        $providerUser = Auth::guard('provider')->user();

        if ($providerUser) {
            $client = Client::query()
                ->where('client_slug', $client->client_slug)
                ->where('provider_slug', $providerUser->provider_slug)
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

        // Provider-facing composition: when accessed via provider auth, append pickups/violations/payments.
        if ($providerUser) {
            $client->setAttribute('pickups', Pickup::query()
                ->where('provider_slug', $providerUser->provider_slug)
                ->where('client_slug', $client->client_slug)
                ->orderByDesc('created_at')
                ->get()
                ->toArray());

            $client->setAttribute('violations', Violation::query()
                ->where('provider_slug', $providerUser->provider_slug)
                ->where('client_slug', $client->client_slug)
                ->orderByDesc('created_at')
                ->get()
                ->toArray());

            $client->setAttribute('payments', Payment::query()
                ->where('provider_slug', $providerUser->provider_slug)
                ->where('client_slug', $client->client_slug)
                ->orderByDesc('created_at')
                ->get()
                ->toArray());
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Client details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $client->load('group', 'bins')->toArray()
        );
    }

    public function updateStatus(StatusRequest $request)
    {
        $data           = $request->validated();

        $user = Auth::guard('provider')->user();

        // Tenant isolation: provider can only update their own clients.
        $client = Client::query()
            ->where('client_slug', $data['client_slug'])
            ->where('provider_slug', $user->provider_slug)
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
            data: $client->load('group', 'bins')->toArray()
        );
    }

    public function updateClientProfile(UpdateClientProfileRequest $request, Client $client)
    {
        $providerUser = Auth::guard('provider')->user();
        $clientUser = Auth::guard('client')->user();

        $query = Client::query()->where('client_slug', $client->client_slug);

        if ($providerUser) {
            $query->where('provider_slug', $providerUser->provider_slug);
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

        $this->ensureRegistrationBin($client);
        $client->refresh();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Client details updated successfully",
            status_code: self::API_SUCCESS,
            data: $client->toArray()
        );
    }

    public function deleteClient(Client $client)
    {
        $user = Auth::guard('provider')->user();

        $deleted = Client::query()
            ->where('client_slug', $client->client_slug)
            ->where('provider_slug', $user->provider_slug)
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

            $bin = Bin::query()
                ->where('bin_code', $qrData['bin_code'])
                ->where('provider_slug', $providerUser->provider_slug)
                ->where('status', 'active')
                ->first();

            $client = Client::where('client_slug', $qrData['client_slug'])
                ->where('provider_slug', $providerUser->provider_slug)
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
        if (empty($client->bin_code)) {
            return;
        }

        Bin::query()->updateOrCreate(
            ['bin_code' => $client->bin_code],
            [
                'bin_slug' => (string) Str::uuid(),
                'client_slug' => $client->client_slug,
                'provider_slug' => $client->provider_slug,
                'product_slug' => null,
                'source' => 'registration',
                'status' => 'active',
            ]
        );
    }
}
