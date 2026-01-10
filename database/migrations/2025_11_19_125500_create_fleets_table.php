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
        Schema::create('fleets', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('vehicle_make')->nullable();
            $table->string('model')->nullable();
            $table->string('manufacture_year')->nullable();
            $table->string('license_plate')->nullable();
            $table->string('bin_capacity')->nullable();
            $table->string('color')->nullable();
            $table->string('owner_first_name')->nullable();
            $table->string('owner_last_name')->nullable();
            $table->string('owner_phone_number')->nullable();
            $table->string('owner_address')->nullable();
            $table->string('provider_id')->nullable();
            $table->timestamp('insurance_expiry_date')->nullable();
            $table->string('insurance_policy_number')->nullable();
            $table->longText('vehicle_images')->nullable();
            $table->longText('vehicle_registration_certificate_image')->nullable();
            $table->longText('vehicle_insurance_certificate_image')->nullable();
            $table->longText('vehicle_roadworthy_certificate_image')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleets');
    }
};
