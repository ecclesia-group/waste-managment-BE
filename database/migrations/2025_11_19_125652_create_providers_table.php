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
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('provider_slug')->unique();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('business_name')->nullable();
            $table->string('district_assembly')->nullable();
            $table->string('business_registration_number')->nullable();
            $table->string('gps_address')->nullable();
            $table->string('email')->unique();
            $table->string('phone_number')->nullable();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->longText('business_certificate_image')->nullable();
            $table->longText('district_assembly_contract_image')->nullable();
            $table->longText('tax_certificate_image')->nullable();
            $table->longText('epa_permit_image')->nullable();
            $table->string('zone_slug')->nullable();
            $table->string('status')->default('active');
            $table->string('region')->nullable();
            $table->string('location')->nullable();
            $table->longText('profile_image')->nullable();
            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
