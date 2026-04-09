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
            $table->string('name')->unique();
            $table->string('module')->index();
            $table->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });

        foreach (['admins', 'providers', 'facilities', 'district_assemblies'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'parent_slug')) {
                    $table->string('parent_slug')->nullable()->after('id');
                }
                if (! Schema::hasColumn($tableName, 'is_main')) {
                    $table->boolean('is_main')->default(true)->after('parent_slug');
                }
                if (! Schema::hasColumn($tableName, 'role_slug')) {
                    $table->uuid('role_slug')->nullable()->after('is_main');
                    $table->index('role_slug');
                }
            });
        }

        // If a legacy roles table exists with JSON permissions, remove that JSON column.
        if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'permissions')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('permissions');
            });
        }
    }

    public function down(): void
    {
        foreach (['admins', 'providers', 'facilities', 'district_assemblies'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'role_slug')) {
                    $table->dropColumn('role_slug');
                }
                if (Schema::hasColumn($tableName, 'is_main')) {
                    $table->dropColumn('is_main');
                }
                if (Schema::hasColumn($tableName, 'parent_slug')) {
                    $table->dropColumn('parent_slug');
                }
            });
        }

        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};

