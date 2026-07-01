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
            $table->foreignId('zone_id')->constrained('zones')->cascadeOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['provider_slug', 'zone_id'], 'provider_zones_unique');
            $table->index(['zone_id', 'status'], 'provider_zones_zone_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_zones');
    }
};
