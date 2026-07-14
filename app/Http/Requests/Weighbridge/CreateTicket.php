<?php

namespace App\Http\Requests\Weighbridge;

use Illuminate\Foundation\Http\FormRequest;

class CreateTicket extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider_slug' => ['required', 'string', 'exists:providers,provider_slug'],
            'fleet_slug' => ['nullable', 'string', 'exists:fleets,fleet_slug'],
            'fleet_code' => ['nullable', 'string'],
            'gross_weight' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['required', 'numeric', 'min:0'],
            // credit = pay later; pending_payment = unpaid; paid = already settled offline at desk
            'payment_status' => ['required', 'string', 'in:pending_payment,paid,credit'],
            'payment_method' => ['nullable', 'string', 'in:cash,bank,momo,card,offline,credit'],
            'network' => ['nullable', 'string', 'max:50'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:255'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'transaction_id' => ['nullable', 'string', 'max:100'],
            'scan_status' => ['nullable', 'string', 'in:scanned,unscanned,handover'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
