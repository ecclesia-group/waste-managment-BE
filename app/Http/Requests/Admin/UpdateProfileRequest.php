<?php

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $adminId = $this->resolveAdminId();

        return [
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'nullable|string|max:255',
            'email'         => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('admins', 'email')->ignore($adminId),
            ],
            'phone_number'  => [
                'required',
                'string',
                'max:12',
                Rule::unique('admins', 'phone_number')->ignore($adminId),
            ],
            'profile_image' => 'nullable|starts_with:data:,http://,https://',
        ];
    }

    private function resolveAdminId(): ?int
    {
        $user = $this->user();

        return $user instanceof Admin ? (int) $user->getKey() : null;
    }
}
