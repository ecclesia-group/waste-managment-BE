<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_zones', function (Blueprint $table) {
            $table->id();
            $table->string('provider_slug');
            $table->string('zone_slug');
            $table->timestamp('assigned_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['provider_slug', 'zone_slug'], 'provider_zones_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_zones');
    }
};
