<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->uuid('role_slug')->unique();
            $table->string('name');
            $table->string('actor'); // admin|provider|facility|district_assembly
            $table->string('actor_slug'); // owner slug
            $table->timestamps();
            $table->unique(['actor', 'actor_slug', 'name'], 'roles_actor_owner_name_unique');
            $table->index(['actor', 'actor_slug'], 'roles_actor_owner_idx');
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('permission_slug')->unique();
            $table->string('actor')->nullable();
            $table->string('name');
            $table->string('module')->index();
            $table->timestamps();

            $table->unique(['actor', 'name'], 'permissions_actor_name_unique');
            $table->index(['actor', 'module'], 'permissions_actor_module_idx');
        });

        Schema::create('role_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
