<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('weighbridge_records', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('facility_slug')->nullable();
            $table->string('provider_slug')->nullable();
            $table->string('fleet_slug')->nullable();
            $table->string('fleet_code')->nullable();
            $table->decimal('gross_weight', 10, 2)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('payment_status')->default('paid'); // paid|credit
            $table->string('scan_status')->default('scanned'); // scanned|unscanned|handover
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['facility_slug', 'provider_slug'], 'weighbridge_facility_provider_idx');
            $table->index(['payment_status', 'scan_status'], 'weighbridge_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weighbridge_records');
    }
};
