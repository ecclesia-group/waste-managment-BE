<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_handover_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('requester_provider_slug');
            $table->string('target_provider_slug')->nullable();
            $table->string('title');
            $table->json('waste_types')->nullable();
            $table->text('description')->nullable();
            $table->string('pickup_location')->nullable();
            $table->json('images')->nullable();
            $table->decimal('fee_amount', 10, 2)->default(0);
            $table->string('status')->default('pending'); // pending|accepted|declined|completed|cancelled
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['requester_provider_slug', 'status']);
            $table->index(['target_provider_slug', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_handover_requests');
    }
};

