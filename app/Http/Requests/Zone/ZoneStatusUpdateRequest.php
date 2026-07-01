<?php
namespace App\Http\Requests\Zone;

use Illuminate\Foundation\Http\FormRequest;

class ZoneStatusUpdateRequest extends FormRequest
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
            'zone_id' => 'required|exists:zones,id',
            'status'    => 'required|in:active,revoke',
        ];
    }
}
