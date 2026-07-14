<?php
namespace App\Http\Requests\Zone;

use Illuminate\Foundation\Http\FormRequest;

class ZoneCreationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|unique:zones,name',
            'region' => 'required|string',
            'description' => 'nullable|string',
            'locations' => 'required|array',
            'district_assembly' => 'nullable|string|exists:district_assemblies,district_assembly_slug',
            'status' => 'nullable|string|in:active,inactive',
        ];
    }
}
