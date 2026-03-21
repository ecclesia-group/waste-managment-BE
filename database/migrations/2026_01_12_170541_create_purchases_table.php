<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('client_slug');
            $table->integer('number_of_items');
            $table->decimal('total_price', 10, 2);
            $table->string('status')->default('pending'); // pending|confirmed|out_for_delivery|delivered|cancelled
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index(['client_slug', 'status'], 'purchase_client_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
