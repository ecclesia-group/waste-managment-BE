<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RegisterRequest;
use App\Models\Admin;
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
}
