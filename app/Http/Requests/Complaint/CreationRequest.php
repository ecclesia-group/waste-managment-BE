<?php
namespace App\Http\Requests\Complaint;

use Illuminate\Foundation\Http\FormRequest;

class CreationRequest extends FormRequest
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
            "status"                 => ["nullable", "string", "in:pending,deactivate,active", "bail"],
            "district_assembly_slug" => ["required", "string", "exists:district_assemblies,district_assembly_slug", "bail"],
        ];
    }
}
