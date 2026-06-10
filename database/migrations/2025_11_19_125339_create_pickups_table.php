<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickups', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('bulk_waste_request_code')->nullable();
            $table->string('client_slug');
            $table->string('title');
            $table->string('category');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('status')->default('pending');
            $table->string('scan_status')->default('pending');
            $table->string('location')->nullable();
            $table->string('provider_slug')->nullable();
            $table->longText('images')->nullable();
            $table->timestamp('pickup_date')->nullable();

            // Route planner link: a pickup is the single stop that ties a plan to a client.
            // Replaces the old route_planner_bin_assignments join table.
            $table->unsignedBigInteger('route_planner_id')->nullable();
            $table->string('group_slug')->nullable();
            $table->timestamp('scanned_at')->nullable();
            $table->timestamp('unscanned_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['provider_slug', 'client_slug', 'bulk_waste_request_code'], 'pickups_provider_client_bulk_idx');
            $table->index(['route_planner_id', 'scan_status'], 'pickups_plan_scan_idx');
            $table->index(['provider_slug', 'group_slug', 'scan_status'], 'pickups_provider_group_scan_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickups');
    }
};
