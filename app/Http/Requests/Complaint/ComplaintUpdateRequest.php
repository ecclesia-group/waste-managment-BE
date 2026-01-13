<?php
namespace App\Http\Requests\Complaint;

use Illuminate\Foundation\Http\FormRequest;

class ComplaintUpdateRequest extends FormRequest
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
            'location'    => ['sometimes', 'string'],
            'description' => ['nullable', 'string'],
            'status'      => ['sometimes', 'string', 'in:pending,open,in_progress,closed'],
            'images'      => ['present', 'nullable', 'array'], // 👈 present is key
            'images.*'    => ['nullable', 'string'],
            'videos'      => ['nullable', 'array'],
            'videos.*'    => ['nullable', 'string'],
        ];
    }

}
