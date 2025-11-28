<?php
namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class ProviderStatusRequest extends FormRequest
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
            "status"        => ["required", "string", "in:pending,deactivate,activate", "bail"],
            //"provider_slug" => ["required", "string", "exists:providers,provider_slug", "bail"],
        ];
    }
}
