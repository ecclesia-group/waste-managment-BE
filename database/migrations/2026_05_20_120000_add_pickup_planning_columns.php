<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bulk_waste_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('bulk_waste_requests', 'payment_status')) {
                $table->string('payment_status')->default('unpaid')->after('amount');
            }
        });

        Schema::table('route_planners', function (Blueprint $table) {
            if (! Schema::hasColumn('route_planners', 'pickup_date')) {
                $table->timestamp('pickup_date')->nullable()->after('status');
            }
            if (! Schema::hasColumn('route_planners', 'pickup_type')) {
                $table->string('pickup_type')->default('normal')->after('pickup_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('route_planners', function (Blueprint $table) {
            if (Schema::hasColumn('route_planners', 'pickup_type')) {
                $table->dropColumn('pickup_type');
            }
            if (Schema::hasColumn('route_planners', 'pickup_date')) {
                $table->dropColumn('pickup_date');
            }
        });

        Schema::table('bulk_waste_requests', function (Blueprint $table) {
            if (Schema::hasColumn('bulk_waste_requests', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
        });

    }
};
