<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waste_handover_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('waste_handover_requests', 'requester_name')) {
                $table->string('requester_name')->nullable()->after('requester_type');
            }
            if (! Schema::hasColumn('waste_handover_requests', 'requester_phone')) {
                $table->string('requester_phone')->nullable()->after('requester_name');
            }
            if (! Schema::hasColumn('waste_handover_requests', 'requester_email')) {
                $table->string('requester_email')->nullable()->after('requester_phone');
            }
            if (! Schema::hasColumn('waste_handover_requests', 'zone_slugs')) {
                $table->json('zone_slugs')->nullable()->after('zone_slug');
            }
            if (! Schema::hasColumn('waste_handover_requests', 'submitted_by_slug')) {
                $table->string('submitted_by_slug')->nullable()->after('requester_email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('waste_handover_requests', function (Blueprint $table) {
            foreach (['submitted_by_slug', 'zone_slugs', 'requester_email', 'requester_phone', 'requester_name'] as $column) {
                if (Schema::hasColumn('waste_handover_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
