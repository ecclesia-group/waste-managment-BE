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
            'client_slug' => 'required|string|exists:clients,client_slug',
            'driver_slug' => 'required|string|exists:drivers,driver_slug',
            'fleet_slug'  => 'required|string|exists:fleets,fleet_slug',
            'zone_slug'   => 'required|string|exists:zones,zone_slug',
            'status'      => 'nullable|string|in:pending,completed,cancalled,progress',
        ];
    }
}
