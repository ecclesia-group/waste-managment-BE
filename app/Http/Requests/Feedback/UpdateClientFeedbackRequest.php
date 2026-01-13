<?php
namespace App\Http\Requests\Feedback;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientFeedbackRequest extends FormRequest
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
            'ratings'  => 'sometimes|integer|min:1|max:5',
            'comments' => 'sometimes|string|max:1000',
            'score'    => 'sometimes|numeric|min:0|max:100',
            'status'   => 'sometimes|string|in:pending,reviewed,resolved',
        ];
    }
}
