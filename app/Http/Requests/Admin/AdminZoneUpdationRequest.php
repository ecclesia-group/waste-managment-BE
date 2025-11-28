<?php
namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminZoneUpdationRequest extends FormRequest
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
            // 'name'        => 'sometimes|string|unique:zones,name,except,id,name',
            // 'name'        => 'sometimes|string|unique:zones,name,' . $this->route('zone_id') . ',id',
            'name'        => 'sometimes|string|unique:zones,name',
            'region'      => 'sometimes|string',
            'description' => 'nullable|nullable|string',
            'locations'   => 'nullable|array',
        ];
    }
}
