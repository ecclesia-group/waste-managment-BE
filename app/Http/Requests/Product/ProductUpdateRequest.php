<?php
namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductUpdateRequest extends FormRequest
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
            "name"              => ["sometimes", "string"],
            "color"             => ["sometimes", "string"],
            "size"              => ["sometimes", "string"],
            "images"            => ["sometimes"],
            'images.*'          => 'nullable|file|image|max:10240',
            "original_price"    => ["sometimes", "numeric", "min:0"],
            "discounted_price"  => ["sometimes", "nullable", "numeric", "min:0"],
            "discount_percentage" => ["sometimes", "nullable", "numeric", "min:0", "max:100"],
            "quantity"          => ["sometimes", "integer", "min:0"],
        ];
    }
}
