<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_scan_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider_slug')->index();
            $table->string('pickup_code')->index();
            $table->string('idempotency_key')->unique();
            $table->timestamp('device_scanned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_scan_events');
    }
};
