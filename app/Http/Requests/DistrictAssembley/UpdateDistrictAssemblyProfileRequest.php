<?php
namespace App\Http\Requests\DistrictAssembley;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDistrictAssemblyProfileRequest extends FormRequest
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
        $district_assembly_slug = $this->route('district_assembly');

        return [
            'region'        => 'required|string|max:100',
            'district'      => 'required|string|max:255',
            'email'         => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('district_assemblies', 'email')->ignore($district_assembly_slug, 'district_assembly_slug'),
            ],

            'phone_number'  => [
                'required',
                'string',
                'max:20',
                Rule::unique('district_assemblies', 'phone_number')->ignore($district_assembly_slug, 'district_assembly_slug'),
            ],

            'gps_address'   => 'required|string|max:255',
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'nullable|string|max:255',

            'profile_image' => 'nullable|starts_with:data:,http://,https://',
        ];
    }
}
