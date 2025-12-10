<?php
namespace App\Http\Requests\Pickup;

use Illuminate\Foundation\Http\FormRequest;

class ProviderPickupCreationRequest extends FormRequest
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
            'title'       => 'required|string',
            'category'    => 'required|string',
            'description' => 'nullable|string',
            'location'    => 'required|string',
            'pickup_date' => 'required|date',
            "images"      => ["required", "array"],
            "images.*"    => ["string", "starts_with:data:,http://,https://"],
        ];
    }
}
