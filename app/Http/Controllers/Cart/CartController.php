<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function getCart(Request $request)
    {
        $user = $request->user();

        $cart = Cart::query()
            ->where('client_slug', $user->client_slug)
            ->with(['items.product'])
            ->first();

        if (! $cart) {
            return self::apiResponse(
                in_error: false,
                message: "Action Successful",
                reason: "Cart is empty",
                status_code: self::API_SUCCESS,
                data: [
                    'items' => [],
                ]
            );
        }

        $items = $cart->items->map(function (CartItem $item) {
            return [
                'product_slug' => $item->product_slug,
                'quantity' => $item->quantity,
                'product' => $item->product?->toArray(),
            ];
        })->values()->toArray();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Cart retrieved successfully",
            status_code: self::API_SUCCESS,
            data: [
                'client_slug' => $cart->client_slug,
                'items' => $items,
            ]
        );
    }

    public function addItem(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'product_slug' => ['required', 'string', 'exists:products,product_slug'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: $validator->errors()->first(),
                status_code: self::API_FAIL,
                data: []
            );
        }

        $data = $validator->validated();

        $cart = Cart::query()->firstOrCreate(
            ['client_slug' => $user->client_slug],
            ['client_slug' => $user->client_slug]
        );

        $item = CartItem::query()->where('cart_id', $cart->id)
            ->where('product_slug', $data['product_slug'])
            ->first();

        if ($item) {
            $item->quantity += $data['quantity'];
            $item->save();
        } else {
            $item = CartItem::query()->create([
                'cart_id' => $cart->id,
                'product_slug' => $data['product_slug'],
                'quantity' => $data['quantity'],
            ]);
        }

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Cart item updated successfully",
            status_code: self::API_SUCCESS,
            data: $item->toArray()
        );
    }

    public function updateItem(Request $request, string $product_slug)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: $validator->errors()->first(),
                status_code: self::API_FAIL,
                data: []
            );
        }

        $cart = Cart::query()->where('client_slug', $user->client_slug)->first();
        if (! $cart) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Cart not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        $item = CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_slug', $product_slug)
            ->first();

        if (! $item) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Cart item not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        $item->quantity = $validator->validated()['quantity'];
        $item->save();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Cart item updated successfully",
            status_code: self::API_SUCCESS,
            data: $item->toArray()
        );
    }

    public function removeItem(Request $request, string $product_slug)
    {
        $user = $request->user();

        $cart = Cart::query()->where('client_slug', $user->client_slug)->first();
        if (! $cart) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Cart not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        $item = CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_slug', $product_slug)
            ->first();

        if (! $item) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Cart item not found",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        $item->delete();

        return self::apiResponse(
            in_error: false,
            message: "Action Successful",
            reason: "Cart item removed successfully",
            status_code: self::API_SUCCESS,
            data: []
        );
    }

    public function checkout(Request $request)
    {
        $user = $request->user();

        $cart = Cart::query()
            ->where('client_slug', $user->client_slug)
            ->with(['items.product'])
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Cart is empty",
                status_code: self::API_NOT_FOUND,
                data: []
            );
        }

        DB::beginTransaction();
        try {
            $totalPrice = 0;
            $numberOfItems = 0;
            $items = [];

            foreach ($cart->items as $cartItem) {
                $product = $cartItem->product;
                if (! $product) {
                    DB::rollBack();
                    return self::apiResponse(
                        in_error: true,
                        message: "Action Failed",
                        reason: "Product not found: " . $cartItem->product_slug,
                        status_code: self::API_FAIL,
                        data: []
                    );
                }

                if ($product->quantity < $cartItem->quantity) {
                    DB::rollBack();
                    return self::apiResponse(
                        in_error: true,
                        message: "Action Failed",
                        reason: "Insufficient quantity for product: " . $product->name,
                        status_code: self::API_FAIL,
                        data: []
                    );
                }

                $itemPrice = $product->discounted_price ?? $product->original_price;
                $totalPrice += $itemPrice * $cartItem->quantity;
                $numberOfItems += $cartItem->quantity;

                $items[] = [
                    'product_slug' => $product->product_slug,
                    'name' => $product->name,
                    'price' => $itemPrice,
                    'quantity' => $cartItem->quantity,
                ];
            }

            $purchase = Purchase::create([
                'client_slug' => $user->client_slug,
                'number_of_items' => $numberOfItems,
                'total_price' => $totalPrice,
                'status' => 'pending',
            ]);

            foreach ($items as $itemData) {
                PurchaseItem::create([
                    'purchase_id' => (string) $purchase->id,
                    'product_slug' => $itemData['product_slug'],
                    'name' => $itemData['name'],
                    'price' => $itemData['price'],
                    'quantity' => $itemData['quantity'],
                ]);

                // Decrease inventory.
                $product = Product::where('product_slug', $itemData['product_slug'])->first();
                $product->quantity -= $itemData['quantity'];
                $product->save();
            }

            // Clear cart after checkout to match the expected checkout flow.
            $cart->items()->delete();

            DB::commit();

            $purchase->load('items');

            return self::apiResponse(
                in_error: false,
                message: "Action Successful",
                reason: "Cart checked out successfully",
                status_code: self::API_SUCCESS,
                data: $purchase->toArray()
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return self::apiResponse(
                in_error: true,
                message: "Action Failed",
                reason: "Checkout failed: " . $e->getMessage(),
                status_code: self::API_FAIL,
                data: []
            );
        }
    }
}

