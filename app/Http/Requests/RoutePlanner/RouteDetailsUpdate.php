<?php
namespace App\Http\Requests\RoutePlanner;

use Illuminate\Foundation\Http\FormRequest;

class RouteDetailsUpdate extends FormRequest
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
            'driver_slug' => 'sometimes|string|exists:drivers,driver_slug',
            'fleet_slug'  => 'sometimes|string|exists:fleets,fleet_slug',
            'pickup_date' => 'sometimes|date',
            'status'      => 'nullable|string|in:pending,completed,cancelled,progress,in_progress',
        ];
    }
}
