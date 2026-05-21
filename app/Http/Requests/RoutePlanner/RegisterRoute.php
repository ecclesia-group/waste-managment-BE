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
            'pickup_date' => 'required|date',
            'group_slugs' => 'nullable|array|min:1',
            'group_slugs.*' => 'string|exists:groups,group_slug',
            'client_slugs' => 'nullable|array|min:1',
            'client_slugs.*' => 'string|exists:clients,client_slug',
            'bulk_request_codes' => 'nullable|array|min:1',
            'bulk_request_codes.*' => 'string|exists:bulk_waste_requests,request_code',
            'status' => 'nullable|string|in:pending,completed,cancelled,progress,in_progress',
        ];
    }
}
