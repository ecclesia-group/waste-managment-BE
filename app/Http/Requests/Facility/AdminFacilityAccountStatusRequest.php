<?php
namespace App\Http\Requests\Facility;

use Illuminate\Foundation\Http\FormRequest;

class AdminFacilityAccountStatusRequest extends FormRequest
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
            "status"        => ["required", "string", "in:pending,deactivate,activate", "bail"],
            "facility_slug" => ["required", "string", "exists:facilities,facility_slug", "bail"],
        ];
    }
}
