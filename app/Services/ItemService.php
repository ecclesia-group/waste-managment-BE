<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Item;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Support\Str;

class ItemService
{
    public static function uniqueItemCode(): string
    {
        do {
            $code = 'ITM-' . Str::upper(Str::random(8));
        } while (Item::query()->where('item_code', $code)->exists());

        return $code;
    }

    public static function assignItemToClient(
        Client $client,
        Product $product,
        string $source = Item::SOURCE_ASSIGNED,
        string $status = Item::STATUS_ACTIVE
    ): Item {
        return Item::query()->create([
            'item_code' => self::uniqueItemCode(),
            'client_slug' => $client->client_slug,
            'provider_slug' => $client->provider_slug,
            'product_slug' => $product->product_slug,
            'source' => $source,
            'status' => $status,
        ]);
    }

    public static function createItemsForPaidPurchase(Purchase $purchase): void
    {
        $purchase->loadMissing('items');
        $client = Client::query()->where('client_slug', $purchase->client_slug)->first();

        if (! $client) {
            return;
        }

        foreach ($purchase->items as $purchaseItem) {
            $product = Product::query()->where('product_slug', $purchaseItem->product_slug)->first();

            if (! $product) {
                continue;
            }

            for ($i = 0; $i < (int) $purchaseItem->quantity; $i++) {
                Item::query()->create([
                    'item_code' => self::uniqueItemCode(),
                    'client_slug' => $client->client_slug,
                    'provider_slug' => $product->provider_slug ?? $client->provider_slug,
                    'product_slug' => $product->product_slug,
                    'purchase_id' => $purchase->id,
                    'source' => Item::SOURCE_PURCHASE,
                    'status' => Item::STATUS_ACTIVE,
                ]);
            }
        }
    }
}
