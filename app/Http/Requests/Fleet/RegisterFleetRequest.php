<?php
namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

class RegisterFleetRequest extends FormRequest
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
            'code'                                   => 'required|string|unique:fleets,code',
            'vehicle_make'                           => 'nullable|string',
            'model'                                  => 'nullable|string',
            'manufacture_year'                       => 'nullable|integer',
            'license_plate'                          => 'nullable|string|unique:fleets,license_plate',
            'bin_capacity'                           => 'nullable|string',
            'color'                                  => 'nullable|string',
            'owner_first_name'                       => 'nullable|string',
            'owner_last_name'                        => 'nullable|string',
            'owner_phone_number'                     => 'nullable|string',
            'owner_address'                          => 'nullable|string',
            'provider_slug'                          => 'nullable|string|exists:providers,provider_slug',
            'insurance_expiry_date'                  => 'nullable|date',
            'insurance_policy_number'                => 'nullable|string|unique:fleets,insurance_policy_number',
            'vehicle_images'                         => 'nullable|array',
            'vehicle_registration_certificate_image' => 'nullable|array',
            'vehicle_insurance_certificate_image'    => 'nullable|array',
            'vehicle_roadworthy_certificate_image'   => 'nullable|array',
            'status'                                 => 'nullable|string|in:active,inactive,maintenance',
        ];
    }
}
