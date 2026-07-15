<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bins') && ! Schema::hasTable('items')) {
            Schema::rename('bins', 'items');
        }

        if (! Schema::hasTable('items')) {
            return;
        }

        if (Schema::hasColumn('items', 'bin_code') && ! Schema::hasColumn('items', 'item_code')) {
            Schema::table('items', function (Blueprint $table) {
                $table->renameColumn('bin_code', 'item_code');
            });
        }

        if (! Schema::hasColumn('items', 'purchase_id')) {
            Schema::table('items', function (Blueprint $table) {
                $table->unsignedBigInteger('purchase_id')->nullable()->after('product_slug');
            });
        }

        $this->dropIndexIfExists('items', 'bins_client_status_idx');
        $this->dropIndexIfExists('items', 'bins_provider_status_idx');

        if (! $this->indexExists('items', 'items_client_status_idx')) {
            Schema::table('items', function (Blueprint $table) {
                $table->index(['client_slug', 'status'], 'items_client_status_idx');
            });
        }

        if (! $this->indexExists('items', 'items_provider_status_idx')) {
            Schema::table('items', function (Blueprint $table) {
                $table->index(['provider_slug', 'status'], 'items_provider_status_idx');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('items') || Schema::hasTable('bins')) {
            return;
        }

        $this->dropIndexIfExists('items', 'items_client_status_idx');
        $this->dropIndexIfExists('items', 'items_provider_status_idx');

        if (Schema::hasColumn('items', 'item_code') && ! Schema::hasColumn('items', 'bin_code')) {
            Schema::table('items', function (Blueprint $table) {
                $table->renameColumn('item_code', 'bin_code');
            });
        }

        if (! $this->indexExists('items', 'bins_client_status_idx')) {
            Schema::table('items', function (Blueprint $table) {
                $table->index(['client_slug', 'status'], 'bins_client_status_idx');
            });
        }

        if (! $this->indexExists('items', 'bins_provider_status_idx')) {
            Schema::table('items', function (Blueprint $table) {
                $table->index(['provider_slug', 'status'], 'bins_provider_status_idx');
            });
        }

        Schema::rename('items', 'bins');
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $result = $connection->selectOne(
            'SELECT COUNT(*) AS cnt FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index]
        );

        return ((int) ($result->cnt ?? 0)) > 0;
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            Schema::table($table, function (Blueprint $blueprint) use ($index) {
                $blueprint->dropIndex($index);
            });
        }
    }
};
