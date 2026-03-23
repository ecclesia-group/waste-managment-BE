<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->text('suspension_reason')->nullable()->after('status');
            $table->text('corrective_action')->nullable()->after('suspension_reason');
            $table->timestamp('suspended_at')->nullable()->after('corrective_action');
        });

        Schema::table('facilities', function (Blueprint $table) {
            $table->text('suspension_reason')->nullable()->after('status');
            $table->text('corrective_action')->nullable()->after('suspension_reason');
            $table->timestamp('suspended_at')->nullable()->after('corrective_action');
        });

        Schema::table('district_assemblies', function (Blueprint $table) {
            $table->text('suspension_reason')->nullable()->after('status');
            $table->text('corrective_action')->nullable()->after('suspension_reason');
            $table->timestamp('suspended_at')->nullable()->after('corrective_action');
        });
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn(['suspension_reason', 'corrective_action', 'suspended_at']);
        });

        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn(['suspension_reason', 'corrective_action', 'suspended_at']);
        });

        Schema::table('district_assemblies', function (Blueprint $table) {
            $table->dropColumn(['suspension_reason', 'corrective_action', 'suspended_at']);
        });
    }
};

