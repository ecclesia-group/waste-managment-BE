<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('purchase_id')->nullable()->after('status')->constrained('purchases')->onDelete('set null');
            $table->foreignId('pickup_id')->nullable()->after('purchase_id')->constrained('pickups')->onDelete('set null');
            $table->string('name')->nullable()->after('phone_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['purchase_id']);
            $table->dropForeign(['pickup_id']);
            $table->dropColumn(['purchase_id', 'pickup_id', 'name']);
        });
    }
};
