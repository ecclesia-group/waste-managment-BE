<?php
namespace App\Http\Requests\Client;

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
            'first_name'      => 'required|string',
            'last_name'       => 'nullable|string',
            'email'           => 'required|string|email|unique:clients,email',
            'phone_number'    => 'required|string|unique:clients,phone_number',
            'gps_address'     => 'required|string',
            'type'            => 'required|string',
            'pickup_location' => 'required|string',
            'bin_size'        => 'required|string',
            'bin_code'        => 'nullable|string',
            'group_id'        => 'nullable|string',
            'qrcode'          => 'nullable|starts_with:data:,http://,https://',
            'profile_image'   => 'nullable|starts_with:data:,http://,https://',
        ];
    }
}
