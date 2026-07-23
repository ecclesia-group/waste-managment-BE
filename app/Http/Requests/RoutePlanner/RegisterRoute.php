<?php
namespace App\Http\Requests\RoutePlanner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRoute extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider_slug' => 'nullable|string|exists:providers,provider_slug',
            'driver_slug' => [
                'required',
                'string',
                Rule::exists('drivers', 'driver_slug')->whereNull('deleted_at'),
            ],
            'fleet_slug' => [
                'required',
                'string',
                Rule::exists('fleets', 'fleet_slug')->whereNull('deleted_at'),
            ],
            'pickup_type' => 'required|string|in:bulk_waste_request,normal',
            'group_slugs' => 'nullable|array',
            'group_slugs.*' => 'required|string|distinct|exists:groups,group_slug',
            'bulk_request_codes' => 'nullable|array',
            'bulk_request_codes.*' => 'required|string|distinct|exists:bulk_waste_requests,request_code',
            'status' => 'nullable|string|in:scheduled,pending,completed,cancelled,progress,in_progress',
        ];
    }

    public function messages(): array
    {
        return [
            'driver_slug.exists' => 'Selected driver was not found or has been deleted.',
            'fleet_slug.exists' => 'Selected fleet was not found or has been deleted.',
            'group_slugs.*.exists' => 'One or more selected groups were not found.',
            'bulk_request_codes.*.exists' => 'One or more bulk waste request codes were not found.',
        ];
    }
}
