<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waste_handover_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('waste_handover_requests', 'requester_type')) {
                $table->string('requester_type')->default('aboboya')->after('requester_provider_slug');
            }
            if (! Schema::hasColumn('waste_handover_requests', 'zone_slug')) {
                $table->string('zone_slug')->nullable()->after('target_provider_slug');
            }
            if (! Schema::hasColumn('waste_handover_requests', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('pickup_location');
            }
            if (! Schema::hasColumn('waste_handover_requests', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (! Schema::hasColumn('waste_handover_requests', 'selected_driver_slug')) {
                $table->string('selected_driver_slug')->nullable()->after('longitude');
            }
            if (! Schema::hasColumn('waste_handover_requests', 'selected_fleet_slug')) {
                $table->string('selected_fleet_slug')->nullable()->after('selected_driver_slug');
            }
            if (! Schema::hasColumn('waste_handover_requests', 'payment_status')) {
                $table->string('payment_status')->default('unpaid')->after('fee_amount');
            }
            if (! Schema::hasColumn('waste_handover_requests', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_status');
            }
        });

        Schema::table('waste_handover_requests', function (Blueprint $table) {
            if (Schema::hasColumn('waste_handover_requests', 'zone_slug')) {
                $table->index(['zone_slug', 'status'], 'whr_zone_status_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('waste_handover_requests', function (Blueprint $table) {
            if (Schema::hasColumn('waste_handover_requests', 'zone_slug')) {
                $table->dropIndex('whr_zone_status_idx');
            }
            foreach ([
                'paid_at',
                'payment_status',
                'selected_fleet_slug',
                'selected_driver_slug',
                'longitude',
                'latitude',
                'zone_slug',
                'requester_type',
            ] as $column) {
                if (Schema::hasColumn('waste_handover_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
