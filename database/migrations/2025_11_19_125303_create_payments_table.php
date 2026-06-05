<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('client_slug');
            $table->string('provider_slug');
            $table->string('payment_type', 64)->nullable();
            $table->string('payable_reference', 128)->nullable();
            $table->string('transaction_id')->unique();
            $table->string('calpay_order_code', 64)->nullable()->unique();
            $table->string('payment_method');
            $table->string('network');
            $table->string('phone_number')->nullable();
            $table->string('name');
            $table->string('client_email')->nullable();
            $table->string('card_name')->nullable();
            $table->string('card_number')->nullable();
            $table->string('card_expiry')->nullable();
            $table->string('card_cvv')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency');
            $table->string('status');
            $table->json('gateway_payload')->nullable();
            $table->json('callback_payload')->nullable();
            $table->string('purchase_id')->nullable();
            $table->string('pickup_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_slug', 'payment_type', 'status'], 'payments_client_type_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
