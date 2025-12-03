<?php
namespace App\Http\Requests\Facility;

use Illuminate\Foundation\Http\FormRequest;

class FacilityOnboardingRequest extends FormRequest
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
            'region'                           => 'required|string|max:100',
            'district'                         => 'required|string|max:255',
            'name'                             => 'required|string|max:255',
            'email'                            => 'required|string|email|max:255|unique:facilities,email',
            'phone_number'                     => 'required|string|max:20|unique:facilities,phone_number',
            'gps_address'                      => 'required|string|max:255',
            'first_name'                       => 'required|string|max:255',
            'last_name'                        => 'nullable|string|max:255',
            'business_certificate_image'       => 'nullable|starts_with:data:,http://,https://',
            'district_assembly_contract_image' => 'nullable|starts_with:data:,http://,https://',
            'tax_certificate_image'            => 'nullable|starts_with:data:,http://,https://',
            'epa_permit_image'                 => 'nullable|starts_with:data:,http://,https://',
            'profile_image'                    => 'nullable|starts_with:data:,http://,https://',
            'type'                             => 'nullable|string',
            'ownership'                       => 'nullable|string',
        ];
    }
}
