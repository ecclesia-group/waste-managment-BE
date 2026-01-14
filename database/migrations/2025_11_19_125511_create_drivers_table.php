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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('driver_slug')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('id_card_type')->nullable();
            $table->string('id_card_number')->nullable();
            $table->string('license_class')->nullable();
            $table->string('license_number')->nullable();
            $table->date('license_date_issued')->nullable();
            $table->date('license_expiry_issued')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone_number')->nullable();
            $table->string('address')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_phone_number')->nullable();
            $table->string('emergency_contract_address')->nullable();
            $table->longText('license_front_image')->nullable();
            $table->longText('license_back_image')->nullable();
            $table->longText('profile_image')->nullable();
            $table->string('status')->default('active');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
