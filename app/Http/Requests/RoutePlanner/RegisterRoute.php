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
            'group_slug'    => 'required|string|exists:groups,group_slug',
            'status'        => 'nullable|string|in:pending,completed,cancalled,progress',
        ];
    }
}
