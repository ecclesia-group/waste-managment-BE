<?php
namespace App\Http\Controllers\DistrictAssembley;

use App\Http\Controllers\Controller;
use App\Http\Requests\DistrictAssembley\AccountStatusRequest;
use App\Http\Requests\DistrictAssembley\OnboardingRequest;
use App\Http\Requests\DistrictAssembley\ProfileUpdateRequest;
use App\Models\DistrictAssembly;
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
}
