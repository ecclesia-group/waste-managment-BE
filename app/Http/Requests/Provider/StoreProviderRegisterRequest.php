<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class StoreProviderRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name'                       => 'required|string',
            'last_name'                        => 'nullable|string',
            'email'                            => 'required|string|email|unique:providers,email',
            'phone_number'                     => 'required|string|unique:providers,phone_number',
            'business_name'                    => 'required|string',
            'district_assembly_slug'                => 'required|string|exists:district_assemblies,district_assembly_slug',
            'business_registration_number'     => 'required|string|unique:providers,business_registration_number',
            'gps_address'                      => 'required|string',
            'business_certificate_image'       => 'nullable|starts_with:data:,http://,https://',
            'district_assembly_contract_image' => 'nullable|starts_with:data:,http://,https://',
            'tax_certificate_image'            => 'nullable|starts_with:data:,http://,https://',
            'epa_permit_image'                 => 'nullable|starts_with:data:,http://,https://',
            'zone_ids'                       => 'nullable|array',
            'zone_ids.*'                     => 'required|integer|distinct|exists:zones,id',
            'region'                           => 'required|string',
            'location'                         => 'required|string',
            'profile_image'                    => 'nullable|starts_with:data:,http://,https://',
            'registration_fee'                 => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
