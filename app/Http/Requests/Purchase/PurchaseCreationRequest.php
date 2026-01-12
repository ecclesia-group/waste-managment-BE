<?php
namespace App\Http\Requests\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseCreationRequest extends FormRequest
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
            "items" => ["required", "array", "min:1"],
            "items.*.product_slug" => ["required", "string", "exists:products,product_slug"],
            "items.*.quantity" => ["required", "integer", "min:1"],
        ];
    }
}
