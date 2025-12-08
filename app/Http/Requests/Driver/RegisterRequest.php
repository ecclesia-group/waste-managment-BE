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
            'first_name'            => 'required|string',
            'middle_name'           => 'nullable|string',
            'last_name'             => 'nullable|string',
            'date_of_birth'         => 'required|string',
            'id_card_type'          => 'required|string',
            'id_card_number'        => 'required|string',
            'license_class'         => 'required|string',
            'license_number'        => 'required|string',
            'license_date_issued'   => 'required|string',
            'license_expiry_issued' => 'required|string',
            'email'                 => 'required|string|email|unique:drivers,email',
            'phone_number'          => 'required|string|unique:drivers,phone_number',
            'address'           => 'required|string',
            'emergency_contact_name'                  => 'required|string',
            'emergency_phone_number'       => 'required|string',
            'emergency_contract_address'              => 'required|string',
            'bin_code'              => 'nullable|string',
            'group_id'              => 'nullable|string',
            'qrcode'                => 'nullable|starts_with:data:,http://,https://',
            'profile_image'         => 'nullable|starts_with:data:,http://,https://',
        ];
    }
}
