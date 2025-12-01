<?php
namespace App\Http\Requests\Facility;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFacilityProfileRequest extends FormRequest
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
        $facility_slug = $this->route('facility');

        return [
            'district'                         => 'required|string|max:255',
            'name'                             => 'required|string|max:255',
            'email'                            => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('facilities', 'email')->ignore($facility_slug, 'facility_slug'),
            ],
            'phone_number'                     => [
                'required',
                'string',
                'max:20',
                Rule::unique('facilities', 'phone_number')->ignore($facility_slug, 'facility_slug'),
            ],
            'gps_address'                      => 'required|string|max:255',
            'first_name'                       => 'required|string|max:255',
            'last_name'                        => 'nullable|string|max:255',

            'business_certificate_image'       => 'nullable|starts_with:data:,http://,https://',
            'district_assembly_contract_image' => 'nullable|starts_with:data:,http://,https://',
            'tax_certificate_image'            => 'nullable|starts_with:data:,http://,https://',
            'epa_permit_image'                 => 'nullable|starts_with:data:,http://,https://',

            'profile_image'                    => 'nullable|starts_with:data:,http://,https://',

            'type'                             => 'nullable|string|max:255',
            'ownership'                        => 'nullable|string|max:255',
        ];
    }
}
