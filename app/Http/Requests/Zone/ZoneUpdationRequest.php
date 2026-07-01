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

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $zone = $this->route('zone');
        $zoneId = $zone instanceof Zone ? $zone->id : $zone;

        return [
            'name'        => ['sometimes', Rule::unique('zones')->ignore($zoneId)],
            'region'      => 'sometimes|string',
            'description' => 'nullable|string',
            'locations'   => 'nullable|array',
        ];
    }
}
