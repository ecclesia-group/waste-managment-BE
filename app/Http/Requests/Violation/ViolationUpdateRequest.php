<?php
namespace App\Http\Requests\Violation;

use Illuminate\Foundation\Http\FormRequest;

class ViolationUpdateRequest extends FormRequest
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
            'type'        => ['sometimes', 'string'],
            'description' => ['sometimes', 'string'],
            'location'    => ['sometimes', 'string'],
            'status'      => ['sometimes', 'string', 'in:pending,open,in_progress,closed'],
            'images'      => ['nullable', 'array'],
            'images.*'    => ['nullable', 'max:10240'], // 10MB max
            'videos'      => ['nullable', 'array'],
            'videos.*'    => ['nullable', 'mimes:mp4,avi,mov,wmv,flv', 'max:51200'], // 50MB max
        ];
    }
}
