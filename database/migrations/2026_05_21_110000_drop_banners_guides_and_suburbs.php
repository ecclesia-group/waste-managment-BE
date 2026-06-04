<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('guides');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('suburb_zone');
        Schema::dropIfExists('suburbs');
    }

    public function down(): void
    {
        // Tables removed intentionally; restore from earlier migrations if needed.
    }
};
