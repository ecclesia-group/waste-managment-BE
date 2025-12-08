<?php
namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Client\RegisterRequest;
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
}
