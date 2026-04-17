<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            $table->string('bulk_waste_request_code')->nullable()->after('code');
            $table->index(['provider_slug', 'client_slug', 'bulk_waste_request_code'], 'pickups_provider_client_bulk_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pickups', function (Blueprint $table) {
            $table->dropIndex('pickups_provider_client_bulk_idx');
            $table->dropColumn('bulk_waste_request_code');
        });
    }
};
