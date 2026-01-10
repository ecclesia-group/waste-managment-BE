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
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('facility_slug')->unique();
            $table->string('region');
            $table->string('district')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('phone_number')->nullable();
            $table->string('password');
            $table->string('gps_address')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('district_assembly')->nullable();
            $table->string('business_registration_name')->nullable();
            $table->longText('business_certificate_image')->nullable();
            $table->longText('district_assembly_contract_image')->nullable();
            $table->longText('tax_certificate_image')->nullable();
            $table->longText('epa_permit_image')->nullable();
            $table->longText('profile_image')->nullable();
            $table->string('type')->nullable();
            $table->string('ownership')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facilities');
    }
};
