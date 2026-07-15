<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->string('client_slug');
            $table->string('provider_slug');
            $table->string('product_slug')->nullable();
            $table->unsignedBigInteger('purchase_id')->nullable();
            $table->string('source')->default('assigned'); // assigned|purchase|manual
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_slug', 'status'], 'items_client_status_idx');
            $table->index(['provider_slug', 'status'], 'items_provider_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
