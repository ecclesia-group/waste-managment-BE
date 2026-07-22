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
        $routeClient = $this->route('client');
        $clientSlug = $routeClient instanceof \App\Models\Client
            ? $routeClient->client_slug
            : (string) $routeClient;

        // Partial updates allowed (provider or client may send only changed fields).
        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->ignore($clientSlug, 'client_slug'),
            ],
            'phone_number' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('clients', 'phone_number')->ignore($clientSlug, 'client_slug'),
            ],
            'gps_address' => ['sometimes', 'required', 'string', 'max:255'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'group_slug' => ['sometimes', 'nullable', 'string', 'exists:groups,group_slug'],
            'fee_id' => ['sometimes', 'nullable', 'integer', 'exists:provider_fees,id'],
            'profile_image' => ['sometimes', 'nullable', 'starts_with:data:,http://,https://'],
        ];
    }
}
