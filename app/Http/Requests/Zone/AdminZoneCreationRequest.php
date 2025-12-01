<?php
namespace App\Http\Requests\Zone;

use Illuminate\Foundation\Http\FormRequest;

class AdminZoneCreationRequest extends FormRequest
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
            'name'        => 'required|string|unique:zones,name',
            'region'      => 'required|string',
            'description' => 'nullable|string',
            'locations'   => 'required|array',
        ];
    }
}
