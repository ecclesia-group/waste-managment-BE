<?php
namespace App\Http\Requests\Group;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GroupUpdation extends FormRequest
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
        $group_slug = $this->route('group');
        dd($group_slug);
        return [
            'name'        => ['sometimes', Rule::unique('groups')->ignore($group_slug, 'group_slug')],
            'description' => 'sometimes|string',
            'locations'   => 'sometimes|array',
            'zones'       => 'nullable|string',
        ];
    }
}
