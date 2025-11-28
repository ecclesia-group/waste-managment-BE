<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminProviderAccountStatusRequest;
use App\Http\Requests\Admin\AdminProviderUpdateProfileRequest;
use App\Models\Provider;

class AdminProviderManagementController extends Controller
{
    public function listProviders()
    {
        $provider = Provider::all();

        if (! $provider) {
            return self::apiResponse(in_error: true, message: "Action Failed", reason: "No providers found", status_code: self::API_FAIL);
        }
        return self::apiResponse(in_error: false, message: "Action Successful", reason: "Providers retrieved successfully", status_code: self::API_SUCCESS, data: $provider->toArray());
    }

    public function getProviderDetails($provider_slug)
    {
        $provider = Provider::where('provider_slug', $provider_slug)->first();
        if (! $provider) {
            return self::apiResponse(in_error: true, message: "Action Failed", reason: "Provider not found", status_code: self::API_FAIL);
        }
        return self::apiResponse(in_error: false, message: "Action Successful", reason: "Provider details retrieved successfully", status_code: self::API_SUCCESS, data: $provider->toArray());
    }

    public function updateProviderStatus(AdminProviderAccountStatusRequest $request)
    {
        $data     = $request->validated();
        $provider = Provider::where('provider_slug', $data['provider_slug'])->first();

        if (! $provider) {
            return self::apiResponse(in_error: true, message: "Action Failed", reason: "Provider not found", status_code: self::API_FAIL);
        }

        $provider->status = $data['status'];
        $provider->save();

        return self::apiResponse(in_error: false, message: "Action Successful", reason: "Provider status updated successfully", status_code: self::API_SUCCESS, data: $provider->toArray());
    }

    public function updateProviderDetails(AdminProviderUpdateProfileRequest $request, $provider_slug)
    {
        $data     = $request->validated();
        $provider = Provider::where('provider_slug', $provider_slug)->first();

        if (! $provider) {
            return self::apiResponse(in_error: true, message: "Action Failed", reason: "Provider not found", status_code: 404);
        }

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

        $provider->update($data);

        return self::apiResponse(in_error: false, message: "Action Successful", reason: "Provider details updated successfully", status_code: self::API_SUCCESS, data: $provider->toArray());
    }
}
