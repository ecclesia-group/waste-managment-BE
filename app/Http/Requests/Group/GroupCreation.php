<?php
namespace App\Http\Requests\Group;

use Illuminate\Foundation\Http\FormRequest;

class GroupCreation extends FormRequest
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
            'name'        => 'required|string|unique:groups,name',
            'description' => 'nullable|string',
            'locations'   => 'required|array',
            'zones'       => 'nullable|array',
        ];
    }
}
