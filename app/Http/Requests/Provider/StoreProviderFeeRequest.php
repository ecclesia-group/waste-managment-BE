<?php

namespace App\Http\Requests\Provider;

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
        $provider = auth('provider')->user();
        $scopeSlug = (bool) ($provider->is_main ?? true)
            ? (string) $provider->provider_slug
            : (string) ($provider->parent_slug ?: $provider->provider_slug);

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('provider_fees', 'name')->where('provider_slug', $scopeSlug),
            ],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
