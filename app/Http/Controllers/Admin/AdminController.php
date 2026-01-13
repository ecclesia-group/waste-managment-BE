<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RegisterRequest;
use App\Models\Admin;
use App\Models\Client;
use App\Models\DistrictAssembly;
use App\Models\Facility;
use App\Models\Provider;
use App\Models\Zone;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $password                  = Str::random(8);
        $data                      = $request->validated();
        $data['admin_slug']        = Str::uuid();
        $data['password']          = $password;
        $data['email_verified_at'] = now();

        // get all images and check for bases 64 or url business_certificate_image, district_assembly_contract_image, tax_certificate_image, epa_permit_image, profile_image
        $image_fields = [
            'profile_image',
        ];

        $data  = static::processImage($image_fields, $data);
        $admin = Admin::create($data);

        self::sendEmail(
            $admin->email,
            email_class: "App\Mail\ActorAccountCreationMail",
            parameters: [
                $admin->email,
                $password,
                $admin->phone_number,
                $login_url = "https://wasteadmin.tripsecuregh.com/login",
            ]
        );

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Admin registered successfully",
            status_code: self::API_SUCCESS,
            data: $admin->toArray()
        );
    }

    // get statistics overview for providers, facilities, clients, district assemblies, zones
    public function getStatisticsOverview()
    {
        $total_providers           = Provider::count();
        $total_facilities          = Facility::count();
        $total_clients             = Client::count();
        $total_district_assemblies = DistrictAssembly::count();
        $total_zones               = Zone::count();

        $data = [
            'total_providers'           => $total_providers,
            'total_facilities'          => $total_facilities,
            'total_clients'             => $total_clients,
            'total_district_assemblies' => $total_district_assemblies,
            'total_zones'               => $total_zones,
        ];

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Statistics overview retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $data
        );
    }
}
