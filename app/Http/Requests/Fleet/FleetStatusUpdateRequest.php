<?php
namespace App\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;

class FleetStatusUpdateRequest extends FormRequest
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
            'fleet_slug' => 'required|string|exists:fleets,fleet_slug',
            'status'     => 'required|string|in:active,inactive,maintenance',
        ];
    }
}
