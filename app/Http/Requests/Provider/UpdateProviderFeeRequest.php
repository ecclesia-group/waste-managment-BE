<?php

namespace App\Http\Requests\Provider;

use App\Models\ProviderFee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProviderFeeRequest extends FormRequest
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

        // Route param is the fee id (string), not a bound ProviderFee model.
        $feeId = $this->route('fee');
        $fee = ProviderFee::query()
            ->where('provider_slug', $scopeSlug)
            ->where('id', $feeId)
            ->first();

        return [
            'name' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('provider_fees', 'name')
                    ->where('provider_slug', $scopeSlug)
                    ->ignore($fee?->id),
            ],
            'amount' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
