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
        Schema::create('district_assemblies', function (Blueprint $table) {
            $table->id();
            $table->string('district_assembly_slug')->unique();
            $table->string('region');
            $table->string('district');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('gps_address')->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('status')->default('pending');
            $table->longText('profile_image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('district_assemblies');
    }
};
