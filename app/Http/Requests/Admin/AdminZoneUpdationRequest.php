<?php
namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        // Get the zone ID from the route parameter (which should be zone_slug, not zone_id)
        $zone_slug = $this->route('zone_slug');
        return [
            'name'        => ['sometimes', Rule::unique('zones')->ignore($zone_slug, 'zone_slug')],
            'region'      => 'sometimes|string',
            'description' => 'nullable|string',
            'locations'   => 'nullable|array',
        ];
    }
}
