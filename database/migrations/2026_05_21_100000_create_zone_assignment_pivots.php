<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_zones', function (Blueprint $table) {
            $table->id();
            $table->string('facility_slug');
            $table->string('zone_slug');
            $table->timestamp('assigned_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['facility_slug', 'zone_slug'], 'facility_zone_unique');
            $table->index(['zone_slug', 'status'], 'facility_zone_zone_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_zones');
    }
};
