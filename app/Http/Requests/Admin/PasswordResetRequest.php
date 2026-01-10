<?php
namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class PasswordResetRequest extends FormRequest
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
            "password"   => ["required", Password::defaults(), "bail"],
            "admin_slug" => ["required", "string", "exists:admins,admin_slug"],
            // "admin_slug" => ["required", "alpha_dash"],
            "otp"        => ["required"],
        ];
    }
}
