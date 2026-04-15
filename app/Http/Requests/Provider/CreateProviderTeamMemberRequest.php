<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class CreateProviderTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:providers,email'],
            'phone_number' => ['required', 'string', 'max:50', 'unique:providers,phone_number'],
            'role_slug' => ['required', 'string', 'exists:roles,role_slug'],
            'status' => ['sometimes', 'string', 'in:active,inactive,suspended'],
        ];
    }
}
