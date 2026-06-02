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
            'latitude'        => 'nullable|numeric|between:-90,90',
            'longitude'       => 'nullable|numeric|between:-180,180',
            'type'            => 'required|string',
            'bin_slug'        => 'nullable|string',
            'group_slug'      => 'nullable|string|exists:groups,group_slug',
            'registration_fee' => 'nullable|numeric|min:0',
            'registration_status' => 'nullable|boolean',
            'profile_image'   => 'nullable|starts_with:data:,http://,https://',
        ];
    }
}
