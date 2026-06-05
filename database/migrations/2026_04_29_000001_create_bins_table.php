<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bins', function (Blueprint $table) {
            $table->id();
            $table->string('bin_slug')->unique();
            $table->string('bin_code')->unique();
            $table->string('client_slug');
            $table->string('provider_slug');
            $table->string('product_slug')->nullable();
            $table->string('source')->default('registration'); // registration|purchase|manual
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_slug', 'status'], 'bins_client_status_idx');
            $table->index(['provider_slug', 'status'], 'bins_provider_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bins');
    }
};
