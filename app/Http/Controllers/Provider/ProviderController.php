<?php
namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\ProviderStatusRequest;
use App\Http\Requests\Provider\StoreProviderRegisterRequest;
use App\Http\Requests\Provider\UpdateProviderProfileRequest;
use App\Models\Provider;
use Illuminate\Support\Str;

use function Symfony\Component\Clock\now;

class ProviderController extends Controller
{
    public function register(StoreProviderRegisterRequest $request)
    {
        $password              = Str::random(8);
        $data                  = $request->validated();
        $data['provider_slug'] = Str::uuid();
        $data['password']      = $password;
        $data['email_verified_at'] = now();

        // get all images and check for bases 64 or url business_certificate_image, district_assembly_contract_image, tax_certificate_image, epa_permit_image, profile_image
        $image_fields = [
            'business_certificate_image',
            'district_assembly_contract_image',
            'tax_certificate_image',
            'epa_permit_image',
            'profile_image',
        ];

        $data     = static::processImage($image_fields, $data);
        $provider = Provider::create($data);

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

    public function index()
    {
        $provider = Provider::all();
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
        $provider     = Provider::where('provider_slug', $provider->provider_slug)->first();
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
}
