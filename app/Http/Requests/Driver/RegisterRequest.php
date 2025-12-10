<?php
namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'first_name'                 => 'required|string',
            'middle_name'                => 'nullable|string',
            'last_name'                  => 'nullable|string',
            'date_of_birth'              => 'required|date',
            'id_card_type'               => 'required|string',
            'id_card_number'             => 'required|string',
            'license_class'              => 'required|string',
            'license_number'             => 'required|string',
            'license_date_issued'        => 'required|date',
            'license_expiry_issued'      => 'required|date',
            'email'                      => 'required|string|email|unique:drivers,email',
            'phone_number'               => 'required|string|unique:drivers,phone_number',
            'address'                    => 'required|string',
            'emergency_contact_name'     => 'required|string',
            'emergency_phone_number'     => 'required|string',
            'emergency_contract_address' => 'required|string',
            'license_front_image'        => 'required|starts_with:data:,http://,https://',
            'license_back_image'         => 'required|starts_with:data:,http://,https://',
            'profile_image'              => 'required|starts_with:data:,http://,https://',
        ];
    }
}
