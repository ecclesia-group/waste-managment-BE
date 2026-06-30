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
            /** Provider (main or team member) who created the request. */
            $table->string('requester_provider_slug');
            /** Provider who accepts and collects the waste. */
            $table->string('target_provider_slug')->nullable();
            $table->string('pickup_location')->nullable();
            $table->string('gps_address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('fleet_type')->nullable();
            $table->string('selected_driver_slug')->nullable();
            $table->string('selected_fleet_slug')->nullable();
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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_handover_requests');
    }
};
