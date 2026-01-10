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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('actor');
            $table->string('actor_id');
            $table->string('transaction_id')->unique();
            $table->string('payment_method');
            $table->string('network');
            $table->string('phone_number')->nullable();
            $table->string('client_email')->nullable();
            $table->string('card_name')->nullable();
            $table->string('card_number')->nullable();
            $table->string('card_expiry')->nullable();
            $table->string('card_cvv')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
