<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('parent_slug')->nullable();
            $table->boolean('is_main')->default(true);
            $table->uuid('role_slug')->nullable();
            $table->string('admin_slug')->unique();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('phone_number')->unique()->nullable();
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->longText('profile_image')->nullable();
            $table->string('status')->default('active');
            $table->text('suspension_reason')->nullable();
            $table->text('corrective_action')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('role_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
