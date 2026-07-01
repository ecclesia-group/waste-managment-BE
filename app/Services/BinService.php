<?php

namespace App\Services;

use App\Models\Bin;
use App\Models\Client;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Support\Str;

class BinService
{
    public static function uniqueBinCode(): string
    {
        do {
            $code = 'BIN-' . Str::upper(Str::random(8));
        } while (Bin::query()->where('bin_code', $code)->exists());

        return $code;
    }

    public static function createRegistrationBin(Client $client, Product $product, bool $active = false): Bin
    {
        return Bin::query()->create([
            'bin_code' => self::uniqueBinCode(),
            'client_slug' => $client->client_slug,
            'provider_slug' => $client->provider_slug,
            'product_slug' => $product->product_slug,
            'source' => Bin::SOURCE_REGISTRATION,
            'status' => $active ? Bin::STATUS_ACTIVE : Bin::STATUS_INACTIVE,
        ]);
    }

    public static function activateRegistrationBins(Client $client): void
    {
        Bin::query()
            ->where('client_slug', $client->client_slug)
            ->where('source', Bin::SOURCE_REGISTRATION)
            ->where('status', Bin::STATUS_INACTIVE)
            ->update(['status' => Bin::STATUS_ACTIVE]);
    }

    public static function createBinsForPaidPurchase(Purchase $purchase): void
    {
        $purchase->loadMissing('items');
        $client = Client::query()->where('client_slug', $purchase->client_slug)->first();

        if (! $client) {
            return;
        }

        foreach ($purchase->items as $item) {
            $product = Product::query()->where('product_slug', $item->product_slug)->first();

            if (! $product || $product->category !== Product::CATEGORY_BIN) {
                continue;
            }

            for ($i = 0; $i < (int) $item->quantity; $i++) {
                Bin::query()->create([
                    'bin_code' => self::uniqueBinCode(),
                    'client_slug' => $client->client_slug,
                    'provider_slug' => $product->provider_slug ?? $client->provider_slug,
                    'product_slug' => $product->product_slug,
                    'source' => Bin::SOURCE_PURCHASE,
                    'status' => Bin::STATUS_ACTIVE,
                ]);
            }
        }
    }
}
