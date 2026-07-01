<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Provider;
use App\Models\ProviderFee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProviderCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $provider = $this->resolveProvider();

        if (! $provider) {
            $this->command?->warn('ProviderCatalogSeeder: no provider found. Set SEED_PROVIDER_EMAIL in .env or create a provider first.');

            return;
        }

        $providerSlug = (string) $provider->provider_slug;

        $fees = [
            ['name' => 'Registration', 'amount' => 0.10],
            ['name' => 'Bin replacement', 'amount' => 0.10],
            ['name' => 'Reconnection', 'amount' => 0.10],
        ];

        foreach ($fees as $fee) {
            ProviderFee::query()->updateOrCreate(
                ['provider_slug' => $providerSlug, 'name' => $fee['name']],
                ['amount' => $fee['amount']]
            );
        }

        $binProducts = [
            ['name' => 'Standard Bin 120L', 'size' => '120L', 'color' => 'Green', 'original_price' => 180, 'discounted_price' => 0.10, 'quantity' => 25],
            ['name' => 'Large Bin 240L', 'size' => '240L', 'color' => 'Blue', 'original_price' => 260, 'discounted_price' => 0.10, 'quantity' => 15],
            ['name' => 'Wheelie Bin 360L', 'size' => '360L', 'color' => 'Grey', 'original_price' => 320, 'discounted_price' => 0.10, 'quantity' => 10],
        ];

        foreach ($binProducts as $item) {
            $product = Product::query()->firstOrCreate(
                [
                    'provider_slug' => $providerSlug,
                    'name' => $item['name'],
                    'category' => Product::CATEGORY_BIN,
                ],
                [
                    'product_slug' => (string) Str::uuid(),
                    'color' => $item['color'],
                    'size' => $item['size'],
                    'original_price' => $item['original_price'],
                    'discounted_price' => $item['discounted_price'],
                    'discount_percentage' => round((($item['original_price'] - $item['discounted_price']) / $item['original_price']) * 100, 2),
                    'quantity' => $item['quantity'],
                    'images' => [],
                ]
            );

            $product->update([
                'color' => $item['color'],
                'size' => $item['size'],
                'original_price' => $item['original_price'],
                'discounted_price' => $item['discounted_price'],
                'discount_percentage' => round((($item['original_price'] - $item['discounted_price']) / $item['original_price']) * 100, 2),
                'quantity' => $item['quantity'],
            ]);
        }

        $wasteProducts = [
            ['name' => 'Refuse sack (pack of 10)', 'size' => 'Large', 'original_price' => 25, 'discounted_price' => 20, 'quantity' => 100],
            ['name' => 'Recycling bag', 'size' => 'Medium', 'original_price' => 15, 'discounted_price' => 12, 'quantity' => 80],
            ['name' => 'Garden waste bag', 'size' => 'XL', 'original_price' => 35, 'discounted_price' => 30, 'quantity' => 40],
        ];

        foreach ($wasteProducts as $item) {
            $product = Product::query()->firstOrCreate(
                [
                    'provider_slug' => $providerSlug,
                    'name' => $item['name'],
                    'category' => Product::CATEGORY_WASTE_ITEM,
                ],
                [
                    'product_slug' => (string) Str::uuid(),
                    'color' => 'Black',
                    'size' => $item['size'],
                    'original_price' => $item['original_price'],
                    'discounted_price' => $item['discounted_price'],
                    'discount_percentage' => round((($item['original_price'] - $item['discounted_price']) / $item['original_price']) * 100, 2),
                    'quantity' => $item['quantity'],
                    'images' => [],
                ]
            );

            $product->update([
                'original_price' => $item['original_price'],
                'discounted_price' => $item['discounted_price'],
                'discount_percentage' => round((($item['original_price'] - $item['discounted_price']) / $item['original_price']) * 100, 2),
                'quantity' => $item['quantity'],
            ]);
        }

        $this->command?->info("ProviderCatalogSeeder: seeded fees and products for provider {$provider->email} ({$providerSlug}).");
    }

    private function resolveProvider(): ?Provider
    {
        $email = env('SEED_PROVIDER_EMAIL');

        if ($email) {
            $byEmail = Provider::query()->where('email', $email)->first();
            if ($byEmail) {
                return $byEmail;
            }
        }

        return Provider::query()
            ->where('is_main', true)
            ->orderBy('id')
            ->first();
    }
}
