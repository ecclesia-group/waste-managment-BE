<?php
namespace App\Http\Requests\Pickup;

use Illuminate\Foundation\Http\FormRequest;

class SetPickupDateRequest extends FormRequest
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
            'code'        => ['required', 'string', 'exists:pickups,code'],
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }
}
