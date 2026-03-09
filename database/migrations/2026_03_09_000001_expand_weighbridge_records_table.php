<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weighbridge_records', function (Blueprint $table) {
            $table->string('facility_slug')->nullable()->after('code');
            $table->string('provider_slug')->nullable()->after('facility_slug');
            $table->string('fleet_slug')->nullable()->after('provider_slug');
            $table->string('fleet_code')->nullable()->after('fleet_slug');
            $table->decimal('gross_weight', 10, 2)->nullable()->after('fleet_code');
            $table->string('payment_status')->default('paid')->after('amount'); // paid|credit
            $table->string('scan_status')->default('scanned')->after('payment_status'); // scanned|unscanned|handover
            $table->text('notes')->nullable()->after('scan_status');

            $table->index(['facility_slug', 'provider_slug'], 'weighbridge_facility_provider_idx');
            $table->index(['payment_status', 'scan_status'], 'weighbridge_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('weighbridge_records', function (Blueprint $table) {
            $table->dropIndex('weighbridge_facility_provider_idx');
            $table->dropIndex('weighbridge_status_idx');

            $table->dropColumn([
                'facility_slug',
                'provider_slug',
                'fleet_slug',
                'fleet_code',
                'gross_weight',
                'payment_status',
                'scan_status',
                'notes',
            ]);
        });
    }
};

