<?php
namespace App\Http\Requests\Zone;

use App\Models\Zone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ZoneUpdationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $zone = $this->route('zone');
        $zoneId = $zone instanceof Zone ? $zone->id : $zone;

        return [
            'name' => ['sometimes', Rule::unique('zones')->ignore($zoneId)],
            'region' => 'sometimes|string',
            'description' => 'nullable|string',
            'locations' => 'nullable|array',
            'district_assembly' => 'nullable|string|exists:district_assemblies,district_assembly_slug',
            'status' => 'nullable|string|in:active,inactive',
        ];
    }
}
