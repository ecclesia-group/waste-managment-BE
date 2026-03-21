<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_planner_bin_assignments', function (Blueprint $table) {
            $table->id();

            // Links a route planner plan to a single bin (client) pickup scan session.
            $table->unsignedBigInteger('route_planner_id');

            // Denormalized fields for fast filtering (no schema updates to existing tables).
            $table->string('provider_slug')->nullable();
            $table->string('driver_slug')->nullable();
            $table->string('fleet_slug')->nullable();
            $table->string('group_slug')->nullable();

            $table->string('client_slug');
            $table->string('pickup_code')->unique(); // pickups.code

            $table->string('scan_status')->default('pending'); // pending|scanned|not_scanned
            $table->timestamp('scanned_at')->nullable();
            $table->timestamp('unscanned_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['route_planner_id', 'scan_status'], 'rpb_assign_status_idx');
            $table->index(['provider_slug', 'group_slug', 'scan_status'], 'rpb_provider_group_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_planner_bin_assignments');
    }
};

