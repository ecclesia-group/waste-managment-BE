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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('client_slug')->unique();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('gps_address')->nullable();
            $table->string('type')->nullable();
            $table->string('pickup_location')->nullable();
            $table->string('bin_size')->nullable();
            $table->string('bin_registration_number')->nullable();
            $table->string('status')->default('pending');
            $table->string('group_id')->nullable();
            $table->longText('qrcode')->nullable();
            $table->longText('profile_image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
