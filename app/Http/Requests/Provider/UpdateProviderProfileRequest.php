<?php

namespace App\Http\Requests\Provider;

use App\Models\Provider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProviderProfileRequest extends FormRequest
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
        $providerId = $this->resolveProviderId();

        return [
            'first_name'                       => 'required|string|max:255',
            'last_name'                        => 'nullable|string|max:255',

            'email'                            => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('providers', 'email')->ignore($providerId),
            ],

            'phone_number'                     => [
                'required',
                'string',
                'max:20',
                Rule::unique('providers', 'phone_number')->ignore($providerId),
            ],

            'business_registration_number'     => [
                'required',
                'string',
                'max:100',
                Rule::unique('providers', 'business_registration_number')
                    ->ignore($providerId)
                    ->whereNull('deleted_at'),
            ],
            'business_name'                    => 'nullable|string|max:255',
            'gps_address'                      => 'required|string|max:255',
            'district_assembly_slug'           => 'nullable|exists:district_assemblies,district_assembly_slug',

            'business_certificate_image'       => 'nullable|starts_with:data:,http://,https://',
            'district_assembly_contract_image' => 'nullable|starts_with:data:,http://,https://',
            'tax_certificate_image'            => 'nullable|starts_with:data:,http://,https://',
            'epa_permit_image'                 => 'nullable|starts_with:data:,http://,https://',

            'region'                           => 'required|string|max:100',
            'location'                         => 'required|string|max:255',
            'profile_image'                    => 'nullable|starts_with:data:,http://,https://',
            // Provider sets this after onboarding (also allowed on admin/MMDA update)
            'registration_fee'                 => ['sometimes', 'nullable', 'numeric', 'min:0'],

            // 'zone_ids'                       => 'nullable|array',
            // 'zone_ids.*'                     => 'required|integer|distinct|exists:zones,id',
        ];
    }

    public function messages(): array
    {
        return [
            'business_registration_number.unique' => 'This business registration number is already assigned to another provider.',
        ];
    }

    private function resolveProviderId(): ?int
    {
        $provider = $this->route('provider');

        if ($provider instanceof Provider) {
            return (int) $provider->getKey();
        }

        if (is_string($provider) && $provider !== '') {
            $id = Provider::query()->where('provider_slug', $provider)->value('id');

            if ($id) {
                return (int) $id;
            }
        }

        $user = $this->user();

        return $user instanceof Provider ? (int) $user->getKey() : null;
    }
}
