<?php

namespace App\Http\Requests\Client;

use App\Models\Product;
use App\Models\Provider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Provider $provider */
        $provider = auth('provider')->user();
        $scopeSlug = (bool) ($provider->is_main ?? true)
            ? (string) $provider->provider_slug
            : (string) ($provider->parent_slug ?: $provider->provider_slug);

        return [
            'first_name'      => 'required|string',
            'last_name'       => 'nullable|string',
            'email'           => 'required|string|email|unique:clients,email',
            'phone_number'    => 'required|string|unique:clients,phone_number',
            'gps_address'     => 'required|string',
            'latitude'        => 'nullable|numeric|between:-90,90',
            'longitude'       => 'nullable|numeric|between:-180,180',
            'type'            => 'required|string',
            'product_slug'    => [
                'required',
                'string',
                Rule::exists('products', 'product_slug')
                    ->where('category', Product::CATEGORY_BIN)
                    ->where('provider_slug', $scopeSlug),
            ],
            'fee_id'          => [
                'required',
                'integer',
                Rule::exists('provider_fees', 'id')->where('provider_slug', $scopeSlug),
            ],
            'group_slug'      => [
                'nullable',
                'string',
                Rule::exists('groups', 'group_slug')->where('provider_slug', $scopeSlug),
            ],
            'profile_image'   => 'nullable|starts_with:data:,http://,https://',
        ];
    }
}
