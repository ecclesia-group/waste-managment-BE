<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'registration_fee')) {
                $table->decimal('registration_fee', 10, 2)->nullable()->after('group_slug');
            }
            if (! Schema::hasColumn('clients', 'registration_status')) {
                $table->boolean('registration_status')->default(false)->after('registration_fee');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'payment_type')) {
                $table->string('payment_type', 64)->nullable()->after('provider_slug');
                $table->index(['client_slug', 'payment_type', 'status'], 'payments_client_type_status_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'payment_type')) {
                $table->dropIndex('payments_client_type_status_idx');
                $table->dropColumn('payment_type');
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'registration_status')) {
                $table->dropColumn('registration_status');
            }
            if (Schema::hasColumn('clients', 'registration_fee')) {
                $table->dropColumn('registration_fee');
            }
        });
    }
};
