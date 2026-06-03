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
            'group_slugs' => 'required_if:pickup_type,normal|array|min:1',
            'group_slugs.*' => 'string|exists:groups,group_slug',
            'bulk_request_codes' => 'required_if:pickup_type,bulk_waste_request|array|min:1',
            'bulk_request_codes.*' => 'string|exists:bulk_waste_requests,request_code',
            'status' => 'nullable|string|in:pending,completed,cancelled,progress,in_progress',
        ];
    }

    public function messages(): array
    {
        return [
            'group_slugs.required_if' => 'For normal pickup plans, select at least one group.',
            'bulk_request_codes.required_if' => 'For bulk pickup plans, select at least one bulk waste request code.',
        ];
    }
}
