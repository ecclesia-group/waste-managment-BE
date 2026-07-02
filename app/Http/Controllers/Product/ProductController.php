<?php
namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductCreationRequest;
use App\Http\Requests\Product\ProductUpdateRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function listProducts(Request $request)
    {
        $category = $request->query('category');
        $q = $request->query('q');
        $user = $request->user();

        $query = Product::query();
        if (isset($user->client_slug)) {
            $ownerSlug = $user->provider_slug;
            $query->forProvider((string) $ownerSlug);
        } elseif (isset($user->provider_slug)) {
            $query->forProvider((string) self::providerSlug($user));
        }

        if (! empty($category)) {
            $query->where('category', (string) $category);
        }

        if (! empty($q)) {
            $query->where('name', 'like', '%'.$q.'%');
        }

        return $this->paginatedApiResponse(
            $query->latest()->paginate($this->perPage($request)),
            'Products retrieved successfully'
        );
    }

    public function getProductDetails(Request $request, Product $product)
    {
        $user = $request->user();
        if (isset($user->client_slug) && (string) $product->provider_slug !== (string) $user->provider_slug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to view this product",
                status_code: self::API_FAIL,
                data: []
            );
        }

        if (isset($user->provider_slug) && (string) $product->provider_slug !== (string) self::providerSlug($user)) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to view this product",
                status_code: self::API_FAIL,
                data: []
            );
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Product details retrieved successfully",
            status_code: self::API_SUCCESS,
            data: $product->toArray()
        );
    }

    public function createProduct(ProductCreationRequest $request)
    {
        $data = $request->validated();
        $actorSlug = self::providerSlug($request->user());
        if (! $actorSlug) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Only providers can create products",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $data['product_slug'] = Str::uuid();
        $data['provider_slug'] = $actorSlug;

        $image_fields = ['images'];
        $data = static::processImage($image_fields, $data);

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
        $user = $request->user();
        if (! isset($user->provider_slug) || (string) $product->provider_slug !== (string) self::providerSlug($user)) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to update this product",
                status_code: self::API_FAIL,
                data: []
            );
        }

        $data = $request->validated();
        $data = static::processImage(['images'], $data);

        $product->update($data);
        $product = $product->fresh();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Product updated successfully",
            status_code: self::API_SUCCESS,
            data: $product->load('provider')->toArray()
        );
    }

    public function deleteProduct(Product $product)
    {
        $user = request()->user();
        if (! isset($user->provider_slug) || (string) $product->provider_slug !== (string) self::providerSlug($user)) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Unauthorized to delete this product",
                status_code: self::API_FAIL,
                data: []
            );
        }

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
