<?php
namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientProfileRequest extends FormRequest
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
        $client_slug = $this->route('client');
        return [
            'first_name'      => 'required|string|max:255',
            'last_name'       => 'nullable|string|max:255',

            'email'           => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->ignore($client_slug, 'client_slug'),
            ],

            'phone_number'    => [
                'required',
                'string',
                'max:20',
                Rule::unique('clients', 'phone_number')->ignore($client_slug, 'client_slug'),
            ],

            'gps_address'     => 'required|string|max:255',
            'type'            => 'nullable|string|max:255',
            'pickup_location' => 'nullable|string|max:255',
            'bin_size'        => 'nullable|string|max:255',
            'bin_code'        => 'nullable|string|max:255',
            'group_id'        => 'nullable|string|max:255',

            'qrcode'          => 'nullable|starts_with:data:,http://,https://',
            'profile_image'   => 'nullable|starts_with:data:,http://,https://',
        ];
    }
}
