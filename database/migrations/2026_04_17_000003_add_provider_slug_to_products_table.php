<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('provider_slug')->nullable()->after('product_slug');
            $table->index(['provider_slug', 'category'], 'products_provider_category_idx');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_provider_category_idx');
            $table->dropColumn('provider_slug');
        });
    }
};
