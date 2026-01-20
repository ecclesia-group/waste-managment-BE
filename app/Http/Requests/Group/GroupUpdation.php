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
        $group_slug    = $this->route('group');         // current group slug
        $provider_slug = auth()->user()->provider_slug; // owner provider
        return [
            'name'        => [
                'sometimes',
                Rule::unique('groups')
                    ->where(fn($query) => $query->where('provider_slug', $provider_slug))
                    ->ignore($group_slug, 'group_slug'),
                'string',
            ],
            'description' => 'sometimes|string',
            'locations'   => 'sometimes|array',
            'zones'       => 'nullable|string',
        ];
    }
}
