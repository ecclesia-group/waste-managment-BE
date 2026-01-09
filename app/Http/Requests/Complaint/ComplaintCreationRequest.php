<?php
namespace App\Http\Requests\Complaint;

use Illuminate\Foundation\Http\FormRequest;

class ComplaintCreationRequest extends FormRequest
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
            "client_slug" => ["required", "string", "exists:clients,client_slug"],
            // "code"        => ["required", "string", "unique:complaints,code"],
            "location"    => ["required", "string"],
            "description" => ["nullable", "string"],
            // "status"      => ["required", "string", "in:open,in_progress,closed"],
            'images'      => 'nullable|starts_with:data:,http://,https://',
            // "images"      => ["nullable", "array"],
            // "images.*"    => ["string", "starts_with:data:,http://,https://"],
            'videos'      => 'nullable|array',
        ];
    }
}
