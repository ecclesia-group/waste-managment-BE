<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_handover_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('requester_provider_slug');
            $table->string('requester_type')->default('aboboya');
            $table->string('requester_name')->nullable();
            $table->string('requester_phone')->nullable();
            $table->string('requester_email')->nullable();
            $table->string('submitted_by_slug')->nullable();
            $table->string('target_provider_slug')->nullable();
            $table->string('zone_slug')->nullable();
            $table->json('zone_slugs')->nullable();
            $table->string('title');
            $table->json('waste_types')->nullable();
            $table->text('description')->nullable();
            $table->string('pickup_location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('selected_driver_slug')->nullable();
            $table->string('selected_fleet_slug')->nullable();
            $table->json('images')->nullable();
            $table->decimal('fee_amount', 10, 2)->default(0);
            $table->string('payment_status')->default('unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['requester_provider_slug', 'status']);
            $table->index(['target_provider_slug', 'status']);
            $table->index(['zone_slug', 'status'], 'whr_zone_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_handover_requests');
    }
};
