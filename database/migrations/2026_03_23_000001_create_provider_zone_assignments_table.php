<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_zone_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('provider_slug');
            $table->string('zone_slug');

            // Used for admin/overviews; operational logic can decide how to interpret it.
            $table->timestamp('assigned_at')->nullable();
            $table->string('status')->default('active'); // active | revoked

            $table->timestamps();

            $table->unique(['provider_slug', 'zone_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_zone_assignments');
    }
};

