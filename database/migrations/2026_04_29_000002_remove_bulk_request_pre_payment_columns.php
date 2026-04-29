<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'bulk_waste_request_code')) {
                $table->dropColumn('bulk_waste_request_code');
            }
        });

        Schema::table('bulk_waste_requests', function (Blueprint $table) {
            if (Schema::hasColumn('bulk_waste_requests', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bulk_waste_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('bulk_waste_requests', 'payment_status')) {
                $table->string('payment_status')->default('unpaid')->after('amount');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'bulk_waste_request_code')) {
                $table->string('bulk_waste_request_code')->nullable()->after('pickup_id');
                $table->index('bulk_waste_request_code', 'payments_bulk_request_idx');
            }
        });
    }
};
