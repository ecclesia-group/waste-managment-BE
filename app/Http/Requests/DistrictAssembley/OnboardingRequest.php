<?php
namespace App\Http\Requests\DistrictAssembley;

use Illuminate\Foundation\Http\FormRequest;

class AdminDistrictAssemblyOnboardingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
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
            'email'                            => 'required|string|email|max:255|unique:district_assemblies,email',
            'phone_number'                     => 'required|string|max:20|unique:district_assemblies,phone_number',
            'gps_address'                      => 'required|string|max:255',
            'first_name'                       => 'required|string|max:255',
            'last_name'                        => 'nullable|string|max:255',
            'profile_image'                    => 'nullable|starts_with:data:,http://,https://',
        ];
    }
}
