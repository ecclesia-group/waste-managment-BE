<?php
namespace App\Http\Requests\Violation;

use Illuminate\Foundation\Http\FormRequest;

class ViolationCreationRequest extends FormRequest
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
            "type"        => ["required", "string"],
            "location"    => ["required", "string"],
            "description" => ["nullable", "string"],
            "images"      => ["nullable", "array"],
            'images.*'    => 'nullable', // 10MB max
            'videos'      => ['nullable', 'array'],
            "videos.*"    => ["nullable", "mimes:mp4,avi,mov,wmv,flv", "max:51200"], // 50MB max
        ];
    }
}
