<?php
namespace App\Http\Requests\DistrictAssembley;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class DistrictAssemblyPasswordChangeResetRequest extends FormRequest
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
            "old_password" => ["current_password:district_assembly"],
            "password"     => ["required", Password::defaults(), "confirmed", "bail"],
        ];
    }
}
