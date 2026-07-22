<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\RegisterRequest;
use App\Http\Requests\Client\StatusRequest;
use App\Http\Requests\Client\UpdateClientProfileRequest;
use App\Models\Client;
use App\Models\Item;
use App\Models\Product;
use App\Models\ProviderFee;
use App\Services\ClientLocationGeocodingService;
use App\Services\ItemService;
use App\Traits\PaginatesApiResults;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    use PaginatesApiResults;

    public function register(RegisterRequest $request)
    {
        $user = Auth::user();
        $password = Str::random(8);
        $data = static::formatPhoneNumbersInData($request->validated());
        $providerSlug = (string) self::providerScopeSlug($user);

        if (! ProviderFee::query()->forProvider($providerSlug)->exists()) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: 'Set up registration fees before onboarding clients',
                status_code: self::API_FAIL,
                data: []
            );
        }

        $fee = ProviderFee::query()
            ->where('id', $data['fee_id'])
            ->forProvider($providerSlug)
            ->firstOrFail();

        $data['client_slug'] = Str::uuid();
        $data['password'] = $password;
        $data['provider_slug'] = $providerSlug;
        $data['email_verified_at'] = now();
        $data['registration_fee'] = round((float) $fee->amount, 2);
        $data['registration_status'] = $data['registration_fee'] <= 0;

        $image_fields = ['profile_image'];
        $data = static::processImage($image_fields, $data);
        $data = $this->applyGeocodedCoordinates($data);

        // Item is assigned by provider after registration payment succeeds.
        $client = Client::create($data)->fresh(['fee', 'group', 'items.product']);

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
            message: 'Action Successful',
            reason: 'Client registered successfully. Assign an item after registration payment.',
            status_code: self::API_SUCCESS,
            data: array_merge($client->toArray(), [
                'requires_registration_payment' => $client->requiresRegistrationPayment(),
            ])
        );
    }

    /**
     * Provider assigns a product to a client after registration fee is paid.
     * Decrements product quantity unless stock is unlimited (-1).
     */
    public function assignItem()
    {
        $user = Auth::guard('provider')->user();
        $providerSlug = (string) self::providerScopeSlug($user);

        $data = request()->validate([
            'client_slug' => ['required', 'string', 'exists:clients,client_slug'],
            'product_slug' => ['required', 'string', 'exists:products,product_slug'],
        ]);

        $client = Client::query()
            ->where('client_slug', $data['client_slug'])
            ->forProvider($providerSlug)
            ->first();

        if (! $client) {
            return self::apiResponse(true, 'Action Failed', 'Client not found', self::API_NOT_FOUND, []);
        }

        if ($client->requiresRegistrationPayment()) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Failed',
                reason: 'Client must pay registration fee before an item can be assigned',
                status_code: self::API_FAIL,
                data: []
            );
        }

        $product = Product::query()
            ->where('product_slug', $data['product_slug'])
            ->forProvider($providerSlug)
            ->first();

        if (! $product) {
            return self::apiResponse(true, 'Action Failed', 'Product not found for this provider', self::API_NOT_FOUND, []);
        }

        $unlimited = (int) $product->quantity === -1;
        if (! $unlimited && (int) $product->quantity < 1) {
            return self::apiResponse(true, 'Action Failed', 'Selected product is out of stock', self::API_FAIL, []);
        }

        $item = DB::transaction(function () use ($client, $product, $unlimited) {
            $item = ItemService::assignItemToClient($client, $product, Item::SOURCE_ASSIGNED);
            if (! $unlimited) {
                $product->decrement('quantity');
            }

            return $item;
        });

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Item assigned to client successfully',
            status_code: self::API_SUCCESS,
            data: [
                'client' => $client->fresh(['fee', 'group'])->toArray(),
                'item' => $item->load('product')->toArray(),
            ]
        );
    }

    public function allClients()
    {
        $user = Auth::user();
        $ownerSlug = self::providerScopeSlug($user);
        $clients = Client::query()
            ->forProvider((string) $ownerSlug)
            ->with(['items.product'])
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
                ->forProvider((string) self::providerScopeSlug($providerUser))
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
            data: $client->load(['items.product'])->toArray()
        );
    }

    public function updateStatus(StatusRequest $request)
    {
        $data = $request->validated();

        $user = Auth::guard('provider')->user();
        $ownerSlug = self::providerScopeSlug($user);

        $client = Client::query()
            ->where('client_slug', $data['client_slug'])
            ->forProvider((string) $ownerSlug)
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
            data: $client->load(['items.product'])->toArray()
        );
    }

    public function updateClientProfile(UpdateClientProfileRequest $request, Client $client)
    {
        $providerUser = Auth::guard('provider')->user();
        $clientUser = Auth::guard('client')->user();

        $query = Client::query()->where('client_slug', $client->client_slug);

        if ($providerUser) {
            $query->forProvider((string) self::providerScopeSlug($providerUser));
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

        $data = static::formatPhoneNumbersInData($request->validated());
        $image_fields = ['profile_image'];
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

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Client details updated successfully",
            status_code: self::API_SUCCESS,
            data: $client->fresh()->load(['items.product'])->toArray()
        );
    }

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
        $ownerSlug = self::providerScopeSlug($user);

        $deleted = Client::query()
            ->where('client_slug', $client->client_slug)
            ->forProvider((string) $ownerSlug)
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

    public function scanQRCode()
    {
        $providerUser = request()->user();

        $data = request()->validate([
            'qrcode_data' => 'required|string',
        ]);

        try {
            $qrData = json_decode($data['qrcode_data'], true);

            $itemCode = $qrData['item_code'] ?? $qrData['bin_code'] ?? null;

            if (! $qrData || ! isset($qrData['client_slug']) || ! $itemCode) {
                return self::apiResponse(
                    in_error: true,
                    message: "Action Failed",
                    reason: "Invalid QR code data",
                    status_code: self::API_FAIL,
                    data: []
                );
            }

            $ownerSlug = self::providerScopeSlug($providerUser);

            $item = Item::query()
                ->where('item_code', $itemCode)
                ->forProvider((string) $ownerSlug)
                ->where('status', Item::STATUS_ACTIVE)
                ->first();

            $client = Client::query()
                ->where('client_slug', $qrData['client_slug'])
                ->forProvider((string) $ownerSlug)
                ->first();

            if (! $client || ! $item || $item->client_slug !== $client->client_slug) {
                return self::apiResponse(
                    in_error: true,
                    message: "Action Failed",
                    reason: "Client or item not found",
                    status_code: self::API_NOT_FOUND,
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
                    'item_code'       => $item->item_code,
                    'bin_code'        => $item->item_code,
                    'bin_size'        => $item->product?->size,
                    'item'            => $item->load('product')->toArray(),
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

    public function myItems()
    {
        $client = Client::query()
            ->where('client_slug', request()->user()->client_slug)
            ->firstOrFail();

        $items = $client->items()->with('product')->orderByDesc('id')->get();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Items retrieved successfully',
            status_code: self::API_SUCCESS,
            data: $items->toArray()
        );
    }
}
