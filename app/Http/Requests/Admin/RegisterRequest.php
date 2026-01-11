<?php
namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'first_name'      => 'required|string',
            'last_name'       => 'nullable|string',
            'email'           => 'required|string|email|unique:admins,email',
            'phone_number'    => 'required|string|unique:admins,phone_number',
            'profile_image'   => 'nullable|starts_with:data:,http://,https://',
        ];
    }
}
