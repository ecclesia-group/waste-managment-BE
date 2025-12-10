<?php
namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
        $driver_slug = $this->route('driver');
        return [
            'first_name'                 => 'required|string|max:255',
            'middle_name'                => 'nullable|string|max:255',
            'last_name'                  => 'nullable|string|max:255',
            'date_of_birth'              => 'required|date',
            'id_card_type'               => 'sometimes|string',
            'id_card_number'             => 'sometimes|string',
            'license_class'              => 'sometimes|string',
            'license_number'             => 'sometimes|string',
            'license_date_issued'        => 'required|date',
            'license_expiry_issued'      => 'required|date',

            'email'                      => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('drivers', 'email')->ignore($driver_slug, 'driver_slug'),
            ],

            'phone_number'               => [
                'required',
                'string',
                'max:20',
                Rule::unique('drivers', 'phone_number')->ignore($driver_slug, 'driver_slug'),
            ],

            'address'                    => 'required|string|max:255',

            'license_front_image'        => 'nullable|starts_with:data:,http://,https://',
            'license_back_image'         => 'nullable|starts_with:data:,http://,https://',
            'profile_image'              => 'nullable|starts_with:data:,http://,https://',

            'emergency_contact_name'     => 'nullable|string|max:100',
            'emergency_phone_number'     => 'nullable|string|max:100',
            'emergency_contract_address' => 'nullable|string|max:255',
        ];
    }
}
