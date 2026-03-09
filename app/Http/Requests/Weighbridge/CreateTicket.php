<?php

namespace App\Http\Requests\Weighbridge;

use Illuminate\Foundation\Http\FormRequest;

class CreateTicket extends FormRequest
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
            'provider_slug' => ['required', 'string', 'exists:providers,provider_slug'],
            'fleet_slug' => ['nullable', 'string', 'exists:fleets,fleet_slug'],
            'fleet_code' => ['nullable', 'string'],
            'gross_weight' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_status' => ['required', 'string', 'in:paid,credit'],
            'scan_status' => ['nullable', 'string', 'in:scanned,unscanned,handover'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
