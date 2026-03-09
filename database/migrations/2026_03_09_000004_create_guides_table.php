<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guides', function (Blueprint $table) {
            $table->id();
            $table->string('guide_slug')->unique();
            $table->string('title');
            $table->string('category')->default('other');
            $table->longText('content')->nullable();
            $table->json('attachments')->nullable();
            $table->string('audience')->default('all'); // client|provider|all
            $table->string('status')->default('active'); // active|inactive
            $table->timestamps();
            $table->softDeletes();

            $table->index(['audience', 'status', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guides');
    }
};

