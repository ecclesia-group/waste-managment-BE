<?php
namespace App\Http\Requests\RoutePlanner;

use Illuminate\Foundation\Http\FormRequest;

class RouteDetailsUpdate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('pickup_type') === 'normal') {
            $this->merge(['bulk_request_codes' => []]);
        }

        if ($this->input('pickup_type') === 'bulk_waste_request') {
            $this->merge(['group_slugs' => []]);
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'driver_slug' => 'sometimes|string|exists:drivers,driver_slug',
            'fleet_slug'  => 'sometimes|string|exists:fleets,fleet_slug',
            'pickup_date' => 'sometimes|date',
            'status'      => 'nullable|string|in:scheduled,pending,completed,cancelled,progress,in_progress',
            'pickup_type' => 'sometimes|string|in:bulk_waste_request,normal',
            'group_slugs' => 'nullable|array',
            'group_slugs.*' => 'required|string|distinct|exists:groups,group_slug',
            'bulk_request_codes' => 'nullable|array',
            'bulk_request_codes.*' => 'required|string|distinct|exists:bulk_waste_requests,request_code',
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
