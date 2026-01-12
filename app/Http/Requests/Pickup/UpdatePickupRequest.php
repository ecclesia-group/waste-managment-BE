<?php
namespace App\Http\Requests\Pickup;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePickupRequest extends FormRequest
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
            'client_slug' => 'sometimes|string|exists:clients,client_slug',
            'title'       => ['sometimes', 'string'],
            'category'    => ['sometimes', 'string'],
            'description' => ['sometimes', 'string'],
            'location'    => ['sometimes', 'string'],
            'pickup_date' => ['sometimes', 'date'],
            'images'      => ['sometimes', 'array'],
            'images.*'    => ['sometimes', 'starts_with:data:,http://,https://'],
        ];
    }
}
