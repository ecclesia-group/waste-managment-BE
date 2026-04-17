<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_waste_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_code')->unique();
            $table->string('client_slug');
            $table->string('provider_slug');
            $table->string('title');
            $table->string('category');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->longText('images')->nullable();
            $table->timestamp('pickup_date')->nullable();
            $table->string('status')->default('pending_approval'); // pending_approval|approved|rejected|scheduled|completed
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['provider_slug', 'status'], 'bwr_provider_status_idx');
            $table->index(['client_slug', 'status'], 'bwr_client_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_waste_requests');
    }
};
