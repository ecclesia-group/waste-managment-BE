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
        /** @var ProviderFee|null $fee */
        $fee = $this->route('fee');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('provider_fees', 'name')
                    ->where('provider_slug', $fee?->provider_slug)
                    ->ignore($fee?->id),
            ],
            'amount' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
