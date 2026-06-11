<?php
namespace App\Http\Requests\RoutePlanner;

use Illuminate\Foundation\Http\FormRequest;

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
            'driver_slug' => 'required|string|exists:drivers,driver_slug',
            'fleet_slug' => 'required|string|exists:fleets,fleet_slug',
            'pickup_type' => 'required|string|in:bulk_waste_request,normal',
            'pickup_date' => 'nullable|date',
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
            'group_slugs.*.exists' => 'One or more selected groups were not found.',
            'bulk_request_codes.*.exists' => 'One or more bulk waste request codes were not found.',
        ];
    }
}
