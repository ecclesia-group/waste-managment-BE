<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('client_groups');
    }

    public function down(): void
    {
        // Pivot removed in favour of clients.group_slug only; no rollback.
    }
};
