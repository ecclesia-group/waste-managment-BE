<?php

namespace App\Http\Requests\Provider;

use App\Support\ProviderOrganisation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProviderFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $ownerSlug = (string) ProviderOrganisation::ownerSlugForUser(auth('provider')->user());

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('provider_fees', 'name')->where('provider_slug', $ownerSlug),
            ],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
