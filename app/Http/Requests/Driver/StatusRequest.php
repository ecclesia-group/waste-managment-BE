<?php
namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

class StatusRequest extends FormRequest
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
            "status"      => ["required", "string", "in:pending,deactivate,activate,on_leave", "bail"],
            "driver_slug" => ["required", "string", "exists:drivers,driver_slug", "bail"],
        ];
    }
}
