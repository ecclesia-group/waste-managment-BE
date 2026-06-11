<?php
namespace App\Http\Requests\DistrictAssembley;

use App\Models\DistrictAssembly;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $districtAssemblyId = $this->resolveDistrictAssemblyId();

        return [
            'region'        => 'required|string|max:100',
            'district'      => 'required|string',
            'email'         => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('district_assemblies', 'email')->ignore($districtAssemblyId),
            ],

            'phone_number'  => [
                'required',
                'string',
                'max:20',
                Rule::unique('district_assemblies', 'phone_number')->ignore($districtAssemblyId),
            ],

            'gps_address'   => 'required|string|max:255',
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'nullable|string|max:255',

            'profile_image' => 'nullable|starts_with:data:,http://,https://',
        ];
    }

    private function resolveDistrictAssemblyId(): ?int
    {
        $districtAssembly = $this->route('district_assembly');

        if ($districtAssembly instanceof DistrictAssembly) {
            return (int) $districtAssembly->getKey();
        }

        if (is_string($districtAssembly) && $districtAssembly !== '') {
            $id = DistrictAssembly::query()
                ->where('district_assembly_slug', $districtAssembly)
                ->value('id');

            return $id ? (int) $id : null;
        }

        return null;
    }
}
