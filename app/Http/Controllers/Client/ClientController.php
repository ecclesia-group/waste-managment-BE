<?php
namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\RegisterRequest;
use App\Http\Requests\Client\StatusRequest;
use App\Http\Requests\Client\UpdateClientProfileRequest;
use App\Models\Client;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $password                  = Str::random(8);
        $data                      = $request->validated();
        $data['client_slug']       = Str::uuid();
        $data['password']          = $password;
        $data['email_verified_at'] = now();

        // get all images and check for bases 64 or url business_certificate_image, district_assembly_contract_image, tax_certificate_image, epa_permit_image, profile_image
        $image_fields = [
            'qrcode',
            'profile_image',
        ];

        $data     = static::processImage($image_fields, $data);
        $provider = Client::create($data);

        self::sendEmail(
            $provider->email,
            email_class: "App\Mail\ActorAccountCreationMail",
            parameters: [
                $provider->email,
                $password,
                $provider->phone_number,
                $login_url = "https://wasteclient.tripsecuregh.com/login",
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

    public function allClients()
    {
        $clients = Client::all();
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
        $client = Client::where('client_slug', $client->client_slug)->first();
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Client details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $client->toArray()
        );
    }

    public function updateStatus(StatusRequest $request)
    {
        $data           = $request->validated();
        $client         = Client::where('client_slug', $data['client_slug'])->first();
        $client->status = $data['status'];
        $client->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Client status updated successfully",
            status_code: self::API_SUCCESS,
            data: $client->toArray()
        );
    }

    public function updateClientProfile(UpdateClientProfileRequest $request, Client $client)
    {
        $data         = $request->validated();
        $image_fields = [
            'qrcode',
            'profile_image',
        ];

        $data = static::processImage($image_fields, $data);
        $client->update($data);
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
        $client->delete();
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Client deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }
}
