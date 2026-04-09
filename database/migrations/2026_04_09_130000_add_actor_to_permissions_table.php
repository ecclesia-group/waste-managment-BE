<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            if (! Schema::hasColumn('permissions', 'actor')) {
                $table->string('actor')->nullable()->after('permission_slug');
                $table->index(['actor', 'module'], 'permissions_actor_module_idx');
            }
        });

        // Relax global unique on name to allow same permission names per actor.
        try {
            Schema::table('permissions', function (Blueprint $table) {
                $table->dropUnique('permissions_name_unique');
            });
        } catch (\Throwable) {
            // no-op if index name differs or already removed
        }

        Schema::table('permissions', function (Blueprint $table) {
            $table->unique(['actor', 'name'], 'permissions_actor_name_unique');
        });
    }

    public function down(): void
    {
        try {
            Schema::table('permissions', function (Blueprint $table) {
                $table->dropUnique('permissions_actor_name_unique');
            });
        } catch (\Throwable) {
        }

        try {
            Schema::table('permissions', function (Blueprint $table) {
                $table->unique('name');
            });
        } catch (\Throwable) {
        }

        Schema::table('permissions', function (Blueprint $table) {
            if (Schema::hasColumn('permissions', 'actor')) {
                $table->dropIndex('permissions_actor_module_idx');
                $table->dropColumn('actor');
            }
        });
    }
};

