<?php
namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductCreationRequest;
use App\Http\Requests\Product\ProductUpdateRequest;
use App\Models\Product;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    // Lists all products
    public function listProducts()
    {
        $products = Product::all();
        if ($products->isEmpty()) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "No products found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Products retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $products->toArray()
        );
    }

    // Gets details of a single product
    public function getProductDetails(Product $product)
    {
        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Product details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $product->toArray()
        );
    }

    // create product
    public function createProduct(ProductCreationRequest $request)
    {
        $data = $request->validated();
        $data['product_slug'] = Str::uuid();

        $image_fields = ['images'];
        $data = static::processImage($image_fields, $data);

        // Calculate discount if discounted_price is provided
        if (isset($data['discounted_price']) && $data['discounted_price'] > 0) {
            $data['discount_percentage'] = (($data['original_price'] - $data['discounted_price']) / $data['original_price']) * 100;
        }

        $product = Product::create($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Product created successfully",
            status_code: self::API_SUCCESS,
            data: $product->toArray()
        );
    }

    public function updateProduct(ProductUpdateRequest $request, Product $product)
    {
        $data = $request->validated();
        $data = static::processImage(['images'], $data);

        // Calculate discount if discounted_price is provided
        if (isset($data['discounted_price']) && $data['discounted_price'] > 0 && isset($data['original_price'])) {
            $data['discount_percentage'] = (($data['original_price'] - $data['discounted_price']) / $data['original_price']) * 100;
        }

        $product->update($data);

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Product updated successfully",
            status_code: self::API_SUCCESS,
            data: $product->toArray()
        );
    }

    public function deleteProduct(Product $product)
    {
        // Delete associated images
        if ($product->images) {
            foreach ($product->images as $image) {
                static::deleteImage($image);
            }
        }

        $product->delete();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Product deleted successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }
}
