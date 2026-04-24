<?php
namespace App\Http\Requests\RoutePlanner;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRoute extends FormRequest
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
        return [
            'provider_slug' => 'required|string|exists:providers,provider_slug',
            'driver_slug'   => 'required|string|exists:drivers,driver_slug',
            'fleet_slug'    => 'required|string|exists:fleets,fleet_slug',
            'pickup_type'   => 'required|string|in:bulk_waste_request,normal',
            'group_slug'    => 'nullable|string|exists:groups,group_slug',
            'group_slugs'   => 'nullable|array|min:1',
            'group_slugs.*' => 'string|exists:groups,group_slug',
            'client_slugs' => 'nullable|array|min:1',
            'client_slugs.*' => 'string|exists:clients,client_slug',
            'bulk_request_codes' => 'nullable|array|min:1',
            'bulk_request_codes.*' => 'string|exists:bulk_waste_requests,request_code',
            'status'        => 'nullable|string|in:pending,completed,cancalled,progress',
        ];
    }
}
