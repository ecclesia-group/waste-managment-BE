<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'calpay_order_code')) {
                $table->string('calpay_order_code', 64)->nullable()->unique()->after('transaction_id');
            }
            if (! Schema::hasColumn('payments', 'payable_reference')) {
                $table->string('payable_reference', 128)->nullable()->after('payment_type');
            }
            if (! Schema::hasColumn('payments', 'gateway_payload')) {
                $table->json('gateway_payload')->nullable()->after('status');
            }
            if (! Schema::hasColumn('payments', 'callback_payload')) {
                $table->json('callback_payload')->nullable()->after('gateway_payload');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            foreach (['callback_payload', 'gateway_payload', 'payable_reference', 'calpay_order_code'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
