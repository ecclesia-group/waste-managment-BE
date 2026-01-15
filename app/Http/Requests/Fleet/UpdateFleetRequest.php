<?php
namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFleetRequest extends FormRequest
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
            'code'                                   => 'sometimes|string|unique:fleets,code,' . $this->fleet->id,
            'vehicle_make'                           => 'sometimes|string',
            'model'                                  => 'sometimes|string',
            'manufacture_year'                       => 'sometimes|integer',
            'license_plate'                          => 'sometimes|string|unique:fleets,license_plate,' . $this->fleet->id,
            'bin_capacity'                           => 'sometimes|string',
            'color'                                  => 'sometimes|string',
            'owner_first_name'                       => 'sometimes|string',
            'owner_last_name'                        => 'sometimes|string',
            'owner_phone_number'                     => 'sometimes|string',
            'owner_address'                          => 'sometimes|string',
            'provider_slug'                          => 'sometimes|string|exists:providers,provider_slug',
            'insurance_expiry_date'                  => 'sometimes|date',
            'insurance_policy_number'                => 'sometimes|string|unique:fleets,insurance_policy_number,' . $this->fleet->id,
            'vehicle_images'                         => 'sometimes|array',
            'vehicle_registration_certificate_image' => 'sometimes|array',
            'vehicle_insurance_certificate_image'    => 'sometimes|array',
            'vehicle_roadworthy_certificate_image'   => 'sometimes|array',
            'status'                                 => 'sometimes|string|in:active,inactive,maintenance',
        ];
    }
}
