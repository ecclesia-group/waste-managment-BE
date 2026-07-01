<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_fees', function (Blueprint $table) {
            $table->id();
            $table->string('provider_slug');
            $table->string('name');
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->index(['provider_slug', 'name'], 'provider_fees_provider_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_fees');
    }
};
