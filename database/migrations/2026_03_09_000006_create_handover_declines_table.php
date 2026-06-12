<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('handover_declines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waste_handover_request_id')->constrained()->cascadeOnDelete();
            $table->string('provider_slug')->index();
            $table->timestamps();

            $table->unique(['waste_handover_request_id', 'provider_slug'], 'handover_declines_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handover_declines');
    }
};
