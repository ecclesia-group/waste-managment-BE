<?php
namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductCreationRequest extends FormRequest
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
            "name"              => ["required", "string"],
            "color"             => ["nullable", "string"],
            "size"              => ["nullable", "string"],
            "images"            => ["nullable"],
            'images.*'          => 'nullable|file|image|max:10240',
            "original_price"    => ["required", "numeric", "min:0"],
            "discounted_price"  => ["nullable", "numeric", "min:0"],
            "discount_percentage" => ["nullable", "numeric", "min:0", "max:100"],
            "quantity"          => ["required", "integer", "min:0"],
        ];
    }
}
