<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminProviderRegisterRequest;
use App\Models\Provider;
use Illuminate\Support\Str;

class AdminOnboardingController extends Controller
{
    public function registerProvider(AdminProviderRegisterRequest $request)
    {
        // Logic to register a provider goes here
        $data                  = $request->validated();
        $data['provider_slug'] = Str::uuid();
        $password              = "Passw0rd@12345";
        $data['password']      = $password;

        // get all images and check for bases 64 or url business_certificate_image, district_assembly_contract_image, tax_certificate_image, epa_permit_image, profile_image
        $image_fields = [
            'business_certificate_image',
            'district_assembly_contract_image',
            'tax_certificate_image',
            'epa_permit_image',
            'profile_image',
        ];

        foreach ($image_fields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $is_base_64   = str_starts_with($data[$field], 'data:');
                $data[$field] = $is_base_64 ? static::base64ImageDecode($data[$field]) : $data[$field];
            }
        }

        // Create Provider
        $provider = Provider::create($data);

        self::sendEmail(
            $provider->email,
            email_class: "App\Mail\ProviderAccountCreationMail",
            parameters: [
                $provider->email,
                $password,
                $provider->phone_number,
            ]
        );

        return self::apiResponse(in_error: false, message: "Action Successful", reason: "Provider registered successfully", status_code: 200, data: $provider->toArray());
    }
}
