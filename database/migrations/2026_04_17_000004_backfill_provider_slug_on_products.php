<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill legacy products from purchase history:
        // product -> latest purchase item's client -> client's provider.
        $rows = DB::table('purchase_items as pi')
            ->join('purchases as p', 'p.id', '=', 'pi.purchase_id')
            ->join('clients as c', 'c.client_slug', '=', 'p.client_slug')
            ->whereNull('pi.deleted_at')
            ->whereNull('p.deleted_at')
            ->whereNull('c.deleted_at')
            ->whereNotNull('c.provider_slug')
            ->orderByDesc('p.created_at')
            ->get([
                'pi.product_slug',
                'c.provider_slug',
            ]);

        $resolved = [];
        foreach ($rows as $row) {
            if (! isset($resolved[$row->product_slug])) {
                $resolved[$row->product_slug] = $row->provider_slug;
            }
        }

        foreach ($resolved as $productSlug => $providerSlug) {
            DB::table('products')
                ->where('product_slug', $productSlug)
                ->whereNull('provider_slug')
                ->update([
                    'provider_slug' => $providerSlug,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // No-op: this is a data backfill migration.
    }
};
